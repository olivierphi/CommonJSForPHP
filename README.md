# CommonJS for PHP

A simple CommonJS spec implementation for PHP for PHP 5.3+.

* Between two beautiful projects based on Zend Framework, Symfony, Drupal, Slim, Silex or whatever modern
[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) heavy Object Oriented frameworks, take
a nap with simple "good old procedural" PHP codestyle! :-)
* Feel comfortable in PHP when you're back from a Node.js or front-end [AMD](https://github.com/amdjs/amdjs-api/wiki/AMD) project.
* Acts as a Service Locator and lazy-loaded dependencies resolver.
* Have fun with isolated PHP code parts! Every Module runs in a automatically generated Closure, and you can freely create
variables and Closures without polluting the PHP global namespaces.
* All your Modules code run in a "Closure sandbox", and Modules communicate between each other only through their ```$require()``` function.
* Can be useful for quick little projects.

This is currently a early Work In Progress. See Unit Tests for current implementation status.
