//
//  poc_mobileApp.swift
//  poc-mobile
//
//  Created by Gabriel Mustiere on 29/05/2026.
//

import HotwireNative
import UIKit
import WebKit

@main
class AppDelegate: UIResponder, UIApplicationDelegate {
    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {
        Hotwire.config.defaultNavigationController = { FullScreenNavigationController() }

        Hotwire.config.makeCustomWebView = { configuration in
            let js = "var s=document.createElement('style');s.textContent='input,select,textarea{font-size:max(16px,1em)!important}';(document.head||document.documentElement).appendChild(s);"
            let script = WKUserScript(source: js, injectionTime: .atDocumentEnd, forMainFrameOnly: false)
            configuration.userContentController.addUserScript(script)
            let webView = WKWebView(frame: .zero, configuration: configuration)
            webView.scrollView.contentInsetAdjustmentBehavior = .always
            return webView
        }

        return true
    }

    func application(_ application: UIApplication, configurationForConnecting connectingSceneSession: UISceneSession, options: UIScene.ConnectionOptions) -> UISceneConfiguration {
        let config = UISceneConfiguration(name: nil, sessionRole: connectingSceneSession.role)
        config.delegateClass = SceneDelegate.self
        return config
    }
}
