import { test, expect, type Page } from '@playwright/test';

// Parcours d'onboarding : démo vitrine en viewport mobile (WebView iOS).
test.use({ viewport: { width: 390, height: 844 } });

async function login(page: Page) {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'admin@example.com');
  await page.fill('input[name="_password"]', 'password');
  await page.click('button[type="submit"]');
  await expect(page.locator('body')).toContainText('Patrimoine total');
}

test('happy path : 4 étapes, analyse, retour-arrière, succès et recommencer', async ({ page }) => {
  await login(page);

  // Accès via le CTA de l'accueil.
  await page.locator('[data-test="open-account-cta"]').click();
  await expect(page.locator('h1')).toContainText('Faisons connaissance');

  // Étape ① — Identité.
  await page.locator('[data-test="civility-mme"]').check({ force: true });
  await page.fill('[data-test="firstName"]', 'Juliette');
  await page.fill('[data-test="lastName"]', 'Lecomte');
  await page.fill('[data-test="birthDate"]', '1990-04-12');
  await page.selectOption('[data-test="nationality"]', 'FR');
  await page.locator('[data-test="onboarding-next"]').click();

  // Étape ② — Coordonnées.
  await expect(page.locator('h1')).toContainText('Vos coordonnées');

  // Retour-arrière : l'état de l'étape ① est conservé.
  await page.locator('[data-test="onboarding-prev"]').click();
  await expect(page.locator('[data-test="firstName"]')).toHaveValue('Juliette');
  await page.locator('[data-test="onboarding-next"]').click();
  await expect(page.locator('h1')).toContainText('Vos coordonnées');

  await page.fill('[data-test="email"]', 'juliette.lecomte@example.fr');
  await page.fill('[data-test="phone"]', '06 24 18 53 09');
  await page.fill('[data-test="addressLine"]', '24 rue des Lilas');
  await page.fill('[data-test="postalCode"]', '75011');
  await page.fill('[data-test="city"]', 'Paris');
  await page.locator('[data-test="city"]').blur();
  await page.locator('[data-test="onboarding-next"]').click();

  // Étape ③ — Pièce d'identité : animation d'analyse factice.
  await expect(page.locator('[data-test="step3"]')).toBeVisible();
  await page.locator('[data-test="onboarding-deposit"]').click();
  await expect(page.locator('[data-test="onboarding-document-verified"]')).toBeVisible({ timeout: 6000 });
  await page.locator('[data-test="onboarding-next"]').click();

  // Étape ④ — Offre + récapitulatif + signature.
  await expect(page.locator('h1')).toContainText('Choisissez votre offre');
  await expect(page.locator('[data-test="onboarding-summary"]')).toContainText('Juliette');
  await page.locator('[data-test="offer-confort"]').check({ force: true });
  await page.locator('[data-test="consent"]').check();
  await page.locator('[data-test="onboarding-submit"]').click();

  // Succès animé + recommencer.
  await expect(page.locator('[data-test="onboarding-success"]')).toBeVisible();
  await expect(page.locator('body')).toContainText('Bienvenue');
  await page.locator('[data-test="onboarding-restart"]').click();
  await expect(page.locator('h1')).toContainText('Faisons connaissance');
  await expect(page.locator('[data-test="firstName"]')).toHaveValue('');
});

test('« remplir un exemple » pré-remplit le parcours', async ({ page }) => {
  await login(page);
  await page.goto('/ouverture-de-compte');

  await page.locator('[data-test="onboarding-fill-example"]').click();
  await page.locator('[data-test="onboarding-next"]').click();

  await expect(page.locator('[data-test="email"]')).toHaveValue('juliette.lecomte@example.fr');
});
