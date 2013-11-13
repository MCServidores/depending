README
======

| Master Build | Develop Build | Dependencies |
| :---: | :---: | :---: |
[![Build Status](https://secure.travis-ci.org/toopay/depending.png?branch=master)](http://travis-ci.org/toopay/depending)|[![Build Status](https://secure.travis-ci.org/toopay/depending.png?branch=develop&)](http://travis-ci.org/toopay/depending)|[![Dependencies Status](https://depending.in/toopay/depending.png)](http://depending.in/toopay/depending)


Official-repository for [Depending](http://depending.in)

Requirements
------------

You'll need a web-server and PHP version 5.3

Installation
------------

Clone this repository

	git clone git://github.com/toopay/depending.git

Enter the main directory :
	
	cd depending

Install composer dependencies :

	curl -s https://getcomposer.org/installer | php
	php composer.phar install

Now we need to prepare **Propel ORM**. First, create a database named **depending** along with database user that have access into it. You also need to create 3 files :

- build.properties. 
- connection.xml. 
- buildtime.xml. 

You could use provided template (build.properties.tpl, connection.xml.tpl, buildtime.xml.tpl) as a starting point.
	
	chmod -R 777 vendor/propel

Now we could run :

	vendor/bin/propel-gen . diff migrate
	vendor/bin/propel-gen -quiet

If everything goes well, then you're ready. See [Propel Documentation](http://propelorm.org/documentation/) furthermore, if you have some issue with above step.

Last, make a **VirtualHost** with **DocumentRoot** pointing to **public/**

Running Tests
-------------

To run the test suite :

	cd /path/to/depending
	vendor/bin/phpunit --coverage-text
	
This project use Continuous Integration and Test Driven Development with Travis-CI for automatic build (build status could be found on top of this document).

**Taufan Aditya**
