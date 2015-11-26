# cts-submit
Command line tool to generate Certificate Transparency information
It is tuned to work with let's encrypt (https://letsencrypt.org/) certificates so it submit the certificate to:
log.certly.io, ct.googleapis.com/aviator, ct.googleapis.com/pilot, ct.googleapis.com/rocketeer, ctlog.api.venafi.com

usage: ./cts-submit.php <certificate> <intermediate>

For example ./cts-submit.php www.example.com_crt.pem lets-encrypt-x1-cross-signed.pem > www.example.com_sct.pem

To use this you will need a recent apache version and add the following command in the virtual host:

SSLOpenSSLConfCmd ServerInfoFile path_to/www.example.com_sct.pem

This will provide the certificate transparency information to the browser using tls extention.

