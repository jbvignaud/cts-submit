# cts-submit
*PHP command line tool to generate Certificate Transparency information*

This script is tuned to work with [Let's Encrypt](https://letsencrypt.org/) certificates so it submits the certificate to:
* ct.googleapis.com/aviator
* ct.googleapis.com/pilot
* ct.googleapis.com/rocketeer
* ctlog.api.venafi.com

## Submitting cts logs

You can use `cts-submit.php` to submit the cert to [Certificate Transparency](https://www.certificate-transparency.org/) servers and get the sct.

usage:  `./cts-submit.php <certificate> <intermediate> <sctdir>`  
example `./cts-submit.php www.example.com_crt.pem lets-encrypt-x1-cross-signed.pem /my/sct/dir > www.example.com_sct.pem`  

The `<sctdir>` is optional. If you do not specify it you'll only get the base64 encoded sct and not binary one written to a file.
If you only use the scts in binary format (by using the sct dir) you do not need to catch the output (`> www.example.com_sct.pem`).

## Concenating cts logs

usage: `./cts-cat.php <ctslog1> <ctslog2>`  
example: `./cts-cat.php /my/sct/dir/firstsite.sct /my/sct/dir/secondsite.sct`

## Server configuration

All methods will provide the certificate transparency information to the browser using tls extention.

### Apache - server info
To use this you will need a recent apache version and add the following command in the virtual host:

```apache
SSLOpenSSLConfCmd ServerInfoFile path_to/www.example.com_sct.pem
```

### Apache - module
Install [`mod_ssl_ct`](https://httpd.apache.org/docs/trunk/mod/mod_ssl_ct.html) and OpenSSL 1.0.2 or later. Follow the documentation on their site for more information. (use `CTStaticSCTs`)

### Nginx
You have to compile nginx with the [`nginx-ct`](https://github.com/grahamedgecombe/nginx-ct) module.
Add this to your nginx config:
```nginx
ssl_ct on;
ssl_ct_static_scts /my/sct/dir;
```

## Testing certificate transparency

You can access your website using Google Chrome or Chromium and click in the address bar on the lock icon to see whether Chrome/Chromium recognizes the certificate transparency.
The [SSLLabs](https://www.ssllabs.com/ssltest/analyze.html) test also checks for certificate transparency. When everything works it should say `Certificate Transparency 	Yes (TLS extension)`.

