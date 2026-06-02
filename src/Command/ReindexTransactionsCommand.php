<?php

declare(strict_types=1);

namespace App\Command;

use App\Search\TransactionFactory;
use App\Search\TransactionIndex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * (Ré)indexe les transactions de démo dans Meilisearch.
 *
 * Chemin optimisé : settings posés en amont (évite un reindex complet) → génération
 * par flux (mémoire constante) → ingestion par lots NDJSON.
 */
#[AsCommand(
    name: 'app:transactions:reindex',
    description: 'Régénère et réindexe les transactions de démo dans Meilisearch',
)]
final class ReindexTransactionsCommand extends Command
{
    private const int DEFAULT_COUNT = 10_000_000;
    private const int BATCH_SIZE = 50_000;

    public function __construct(
        private readonly TransactionIndex $index,
        private readonly TransactionFactory $factory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Nombre de transactions du compte courant', self::DEFAULT_COUNT)
            ->addOption('wait', null, InputOption::VALUE_NONE, 'Attend la fin de l\'indexation Meilisearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $countOption = $input->getOption('count');
        $count = max(0, is_numeric($countOption) ? (int) $countOption : self::DEFAULT_COUNT);
        $wait = true === $input->getOption('wait');

        $io->title(sprintf('Réindexation de ~%s transactions dans Meilisearch', number_format($count, 0, ',', ' ')));

        $started = microtime(true);

        $io->section('Réinitialisation de l\'index');
        $t = microtime(true);
        $this->index->reset();
        $resetSec = microtime(true) - $t;
        $io->writeln(sprintf('  <info>✓</info> index vidé et recréé <comment>(%s)</comment>', self::duration($resetSec)));

        // Settings posés AVANT l'import : sur un index déjà peuplé, les modifier
        // déclencherait un reindex complet des documents (reco Meilisearch).
        $io->section('Configuration des settings');
        $t = microtime(true);
        $this->index->configure();
        $configureSec = microtime(true) - $t;
        $io->writeln(sprintf('  <info>✓</info> attributs searchable/filterable/sortable posés <comment>(%s)</comment>', self::duration($configureSec)));

        $io->section('Import des documents (NDJSON)');
        $progress = $this->createBar($io, $count);
        $progress->start();

        $t = microtime(true);
        $batch = '';
        $inBatch = 0;

        /** @var list<array{uid: int, size: int}> $tasks un lot = une task Meilisearch */
        $tasks = [];

        foreach ($this->factory->generate($count) as $document) {
            $batch .= json_encode($document, \JSON_THROW_ON_ERROR) . "\n";
            ++$inBatch;

            if ($inBatch >= self::BATCH_SIZE) {
                $tasks[] = ['uid' => $this->index->addNdjsonBatch($batch), 'size' => $inBatch];
                $progress->advance($inBatch);
                $batch = '';
                $inBatch = 0;
            }
        }

        if ('' !== $batch) {
            $tasks[] = ['uid' => $this->index->addNdjsonBatch($batch), 'size' => $inBatch];
            $progress->advance($inBatch);
        }

        $progress->finish();
        $importSec = microtime(true) - $t;
        $io->newLine(2);

        $enqueued = $progress->getProgress();
        $rate = $importSec > 0 ? $enqueued / $importSec : 0.0;
        $io->writeln(sprintf(
            '  <info>✓</info> %s documents enfilés en %s <comment>(%s docs/s)</comment>',
            number_format($enqueued, 0, ',', ' '),
            self::duration($importSec),
            number_format($rate, 0, ',', ' '),
        ));

        $waitSec = 0.0;
        if ($wait && [] !== $tasks) {
            // File de tasks FIFO : on attend chaque lot dans l'ordre et on avance la
            // barre de sa taille → progression réelle de l'indexation asynchrone.
            $io->section('Indexation Meilisearch');
            $indexBar = $this->createBar($io, $enqueued);
            $indexBar->start();

            $t = microtime(true);
            foreach ($tasks as $task) {
                $this->index->waitForTask($task['uid']);
                $indexBar->advance($task['size']);
            }
            $indexBar->finish();
            $waitSec = microtime(true) - $t;
            $io->newLine(2);

            $indexRate = $waitSec > 0 ? $enqueued / $waitSec : 0.0;
            $io->writeln(sprintf(
                '  <info>✓</info> indexation terminée en %s <comment>(%s docs/s)</comment>',
                self::duration($waitSec),
                number_format($indexRate, 0, ',', ' '),
            ));
        }

        $io->section('Récapitulatif');
        $rows = [
            ['Réinitialisation', self::duration($resetSec)],
            ['Settings', self::duration($configureSec)],
            ['Import (enqueue)', self::duration($importSec)],
        ];
        if ($wait) {
            $rows[] = ['Indexation (attente)', self::duration($waitSec)];
        }
        $rows[] = new TableSeparator();
        $rows[] = ['<info>Total</info>', '<info>' . self::duration(microtime(true) - $started) . '</info>'];
        $io->table(['Phase', 'Durée'], $rows);

        $io->success($wait
            ? 'Index Meilisearch prêt.'
            : 'Documents enfilés. Meilisearch finit l\'indexation en tâche de fond.');

        return Command::SUCCESS;
    }

    /**
     * Barre enrichie : avancement, % , temps écoulé/estimé, débit live et mémoire.
     * Les placeholders sont calculés au redraw (un par lot) → coût négligeable.
     */
    private function createBar(SymfonyStyle $io, int $count): ProgressBar
    {
        ProgressBar::setPlaceholderFormatterDefinition('throughput', static function (ProgressBar $bar): string {
            $elapsed = time() - $bar->getStartTime();
            if ($elapsed <= 0) {
                return '—';
            }

            return number_format((int) ($bar->getProgress() / $elapsed), 0, ',', ' ');
        });

        $progress = $io->createProgressBar($count);
        $progress->setFormat(
            " %current%/%max% [%bar%] %percent:3s%%\n"
            . '  %elapsed:6s% écoulé · ETA %estimated:-6s% · %throughput% docs/s · %memory:6s%',
        );
        $progress->setRedrawFrequency(self::BATCH_SIZE);

        return $progress;
    }

    private static function duration(float $seconds): string
    {
        if ($seconds < 1.0) {
            return sprintf('%d ms', (int) round($seconds * 1000));
        }

        if ($seconds < 60.0) {
            return sprintf('%.1f s', $seconds);
        }

        $minutes = intdiv((int) $seconds, 60);
        $rest = (int) $seconds % 60;

        return sprintf('%d min %02d s', $minutes, $rest);
    }
}
