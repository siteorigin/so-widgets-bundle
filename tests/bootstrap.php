<?php
/**
 * PHPUnit bootstrap.
 *
 * Loading the autoloader here pulls in Patchwork (via Brain Monkey) before any
 * test file is read. Brain Monkey can only redefine a function if Patchwork was
 * loaded first, so anything that defines WordPress functions has to come after
 * this point.
 */

require_once __DIR__ . '/../vendor/autoload.php';
