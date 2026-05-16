#!/bin/bash
npx wp-env --config .wp-env.tests.json run wordpress "chmod -c ugo+w /var/www/html"
npx wp-env --config .wp-env.tests.json run cli "wp rewrite structure '/%postname%/' --hard"
