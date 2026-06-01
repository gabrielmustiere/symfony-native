package net.technao.poc_mobile

import android.app.Application
import android.content.Context
import dev.hotwire.core.config.Hotwire

/**
 * Point de configuration Hotwire (exécuté avant la création de l'Activity).
 *
 * Branche la fabrique de WebView sur [WebAuthnWebView] afin que toutes les
 * WebView de l'app activent le pont WebAuthn.
 */
class PocMobileApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        Hotwire.config.makeCustomWebView = { context: Context ->
            WebAuthnWebView(context, null)
        }
    }
}
