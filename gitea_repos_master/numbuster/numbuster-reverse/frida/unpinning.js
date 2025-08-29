"use strict";

function okHTTPv3Unpinning() {
  var okhttp3_CertificatePinner_class = null;
  try {
    okhttp3_CertificatePinner_class = Java.use("okhttp3.CertificatePinner");
  } catch (err) {
    console.log("[-] OkHTTPv3 CertificatePinner class not found. Skipping.");
    okhttp3_CertificatePinner_class = null;
  }

  if (okhttp3_CertificatePinner_class != null) {
    try {
      okhttp3_CertificatePinner_class.check.overload(
        "java.lang.String",
        "java.util.List"
      ).implementation = function (str, list) {
        console.log("[+] Bypassing OkHTTPv3 1: " + str);
        return true;
      };
      console.log("[+] Loaded OkHTTPv3 hook 1");
    } catch (err) {
      console.log("[-] Skipping OkHTTPv3 hook 1");
    }

    try {
      okhttp3_CertificatePinner_class.check.overload(
        "java.lang.String",
        "java.security.cert.Certificate"
      ).implementation = function (str, cert) {
        console.log("[+] Bypassing OkHTTPv3 2: " + str);
        return true;
      };
      console.log("[+] Loaded OkHTTPv3 hook 2");
    } catch (err) {
      console.log("[-] Skipping OkHTTPv3 hook 2");
    }

    try {
      okhttp3_CertificatePinner_class.check.overload(
        "java.lang.String",
        "[Ljava.security.cert.Certificate;"
      ).implementation = function (str, cert_array) {
        console.log("[+] Bypassing OkHTTPv3 3: " + str);
        return true;
      };
      console.log("[+] Loaded OkHTTPv3 hook 3");
    } catch (err) {
      console.log("[-] Skipping OkHTTPv3 hook 3");
    }

    try {
      okhttp3_CertificatePinner_class[
        "check$okhttp"
      ].implementation = function (str, obj) {
        console.log("[+] Bypassing OkHTTPv3 4 (4.2+): " + str + " " + JSON.stringify(obj, null));
      };
      console.log("[+] Loaded OkHTTPv3 hook 4 (4.2+)");
    } catch (err) {
      console.log("[-] Skipping OkHTTPv3 hook 4 (4.2+)");
    }
  }
}

function trustManagerBypass() {
  var X509TrustManager = Java.use("javax.net.ssl.X509TrustManager");
  var SSLContext = Java.use("javax.net.ssl.SSLContext");

  // Build fake trust manager
  var TrustManager = Java.registerClass({
    name: "com.sensepost.test.TrustManager",
    implements: [X509TrustManager],
    methods: {
      checkClientTrusted: function (chain, authType) {},
      checkServerTrusted: function (chain, authType) {},
      getAcceptedIssuers: function () {
        return [];
      },
    },
  });

  // Pass our own custom trust manager through when requested
  var TrustManagers = [TrustManager.$new()];
  var SSLContext_init = SSLContext.init.overload(
    "[Ljavax.net.ssl.KeyManager;",
    "[Ljavax.net.ssl.TrustManager;",
    "java.security.SecureRandom"
  );
  SSLContext_init.implementation = function (
    keyManager,
    trustManager,
    secureRandom
  ) {
    console.log("[+] Intercepted trustmanager request");
    SSLContext_init.call(this, keyManager, TrustManagers, secureRandom);
  };

  console.log("[+] Setup custom trust manager");
}

function removeProxy() {
  try {
    var URL = Java.use("java.net.URL");
    URL.openConnection.overload("java.net.Proxy").implementation = function (
      arg1
    ) {
      return this.openConnection();
    };
  } catch (e) {
    console.log("[-] " + e);
  }

  try {
    var Builder = Java.use("okhttp3.OkHttpClient$Builder");
    var mybuilder = Builder.$new();
    Builder.proxy.overload("java.net.Proxy").implementation = function (arg1) {
      console.log('[+} Set proxy ' + arg1 + ' ' + mybuilder);
      return mybuilder;
    };
  } catch (e) {
    console.log("[-] " + e);
  }
}

Java.perform(function () {
  okHTTPv3Unpinning();
  trustManagerBypass();
  removeProxy();
});
