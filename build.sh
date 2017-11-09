#!/bin/bash

# Disable debug
sed -i.bak  "s@^const.DEBUG.*;@const DEBUG = false;@" netease.php

# Generate the no Chinsese translation version
sed -i.bak "s@^const.NEED_TRANSLATION.*;@const NEED_TRANSLATION = false;@" netease.php
tar czf netease_org.aum netease.php INFO

# Generate the with Chinsese translation version
sed -i.bak "s@^const.NEED_TRANSLATION.*;@const NEED_TRANSLATION = true;@" netease.php
tar czf netease_trans.aum netease.php INFO

# Clean up
rm netease.php.bak
git checkout netease.php
