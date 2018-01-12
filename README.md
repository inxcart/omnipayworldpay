# Mollie Omnipay module

This module integrates Mollie in thirty bees with the help of the Omnipay library.

## Building the module

In order to prevent library conflicts in thirty bees this module's dependencies need to be "scoped".
For this we use PHP Scoper (https://github.com/humbug/php-scoper) in the build script.
By default everything in the vendor folder is now scoped to `ThirtyBeesMollie`.

You can build the module by running the build script:
```shell
$ ./build.sh
```
