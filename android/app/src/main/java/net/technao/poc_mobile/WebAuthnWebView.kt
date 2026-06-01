package net.technao.poc_mobile

import android.content.Context
import android.util.AttributeSet
import androidx.webkit.WebSettingsCompat
import androidx.webkit.WebViewFeature
import dev.hotwire.core.turbo.webview.HotwireWebView

/**
 * WebView Hotwire qui active le pont WebAuthn → Credential Manager.
 *
 * Contrairement au WKWebView iOS (passkeys natifs), la WebView Android
 * n'expose `PublicKeyCredential` que si l'app appelle explicitement
 * `setWebAuthenticationSupport`. En mode `FOR_APP`, les appels WebAuthn de
 * la page web sont délégués au Credential Manager système, l'association
 * app ↔ domaine étant validée via Digital Asset Links
 * (`/.well-known/assetlinks.json`).
 *
 * Le garde `isFeatureSupported` couvre les WebView providers trop anciens
 * (< 134) : on dégrade silencieusement plutôt que de crasher.
 */
class WebAuthnWebView(context: Context, attrs: AttributeSet?) : HotwireWebView(context, attrs) {
    init {
        if (WebViewFeature.isFeatureSupported(WebViewFeature.WEB_AUTHENTICATION)) {
            WebSettingsCompat.setWebAuthenticationSupport(
                settings,
                WebSettingsCompat.WEB_AUTHENTICATION_SUPPORT_FOR_APP,
            )
        }
    }
}
