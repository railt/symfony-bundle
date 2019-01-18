# Symfony Bundle for Railt

<p align="center">
    <a href="https://travis-ci.org/railt/symfony-bundle"><img src="https://travis-ci.org/railt/symfony-bundle.svg?branch=1.3.x" alt="Travis CI" /></a>
    <a href="https://scrutinizer-ci.com/g/railt/symfony-bundle/?branch=master"><img src="https://scrutinizer-ci.com/g/railt/symfony-bundle/badges/quality-score.png?b=1.3.x" alt="Scrutinizer CI" /></a>
    <a href="https://scrutinizer-ci.com/g/railt/symfony-bundle/?branch=master"><img src="https://scrutinizer-ci.com/g/railt/symfony-bundle/badges/coverage.png?b=1.3.x" alt="Code coverage" /></a>
    <a href="https://packagist.org/packages/railt/symfony-bundle"><img src="https://poser.pugx.org/railt/symfony-bundle/version?" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/railt/symfony-bundle"><img src="https://poser.pugx.org/railt/symfony-bundle/v/unstable?" alt="Latest Unstable Version"></a>
    <a href="https://raw.githubusercontent.com/railt/symfony-bundle/master/LICENSE"><img src="https://poser.pugx.org/railt/symfony-bundle/license?" alt="License MIT"></a>
</p>

## About

The Symfony Framework Bundle for Railt.

## Installation

> Make sure that you are using at least PHP 7.1

1. `composer require railt/symfony-bundle`
2. Add the `\Railt\SymfonyBundle\RailtBundle::class` into your bundles list.
3. Add a GraphQL route, like:
```yml
app.graphql:
    resource: "@RailtBundle/Resources/config/routing.yml"
    prefix: /graphql
```

> Now you have a GraphQL Server located in `http://localhost/graphql/`

3.1: Or like this:
```yml
app.graphql:
    path: /graphql
    methods: [ 'GET', 'POST', 'PATCH', 'PUT' ]
    defaults:
        _controller: RailtBundle:GraphQL:handle
```

> Now you have a GraphQL Server located in `http://localhost/graphql`

## Configuration

You can configure your application:

```yml
railt:
    # Enable or disable the cache and debug mode
    # - Optional
    # - Default: %kernel.debug%
    debug: '%kernel.debug%'

    # Schema file reference
    # - Optional
    # - Default: '@RailtBundle/Resources/graphql/schema.graphqls'
    schema: '@YourBundle/Resources/graphql/schema.graphqls'

    # Directories where railt will try to load missing type files
    # - Optional
    # - Default: []
    autoload:
        - '@YourBundle/Resources/graphql/'
        - '@YourBundle/Resources/graphql/queries/'
        - '@YourBundle/Resources/graphql/mutations/'

    # Names of extensions (string class name)
    # - Optional
    # - Default: []
    extensions: 
        - Some/Extension
``` 
