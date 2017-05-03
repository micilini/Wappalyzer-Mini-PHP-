# Wappalyzer Mini (PHP)

[Wappalyzer](https://wappalyzer.com/) is a
[cross-platform](https://github.com/AliasIO/Wappalyzer/wiki/Drivers) utility that uncovers the
technologies used on websites. It detects
[content management systems](https://wappalyzer.com/categories/cms),
[eCommerce platforms](https://wappalyzer.com/categories/ecommerce),
[web servers](https://wappalyzer.com/categories/web-servers),
[JavaScript frameworks](https://wappalyzer.com/categories/javascript-frameworks),
[analytics tools](https://wappalyzer.com/categories/analytics) and
[many more](https://wappalyzer.com/applications).

*Licensed under the [GPL](https://github.com/AliasIO/Wappalyzer/blob/master/LICENSE).*

## Why is called Wappalyzer Mini ?

The difference is that the original version of wappalyzer, uses some more technologies like: ```NodeJS```, ```V8JS``` ... and many others to check the technologies used in a particular website.

While this version only uses ```PHP``` to do verifications, there is no need to have to use ```NodeJS```, ```V8JS``` ... and not have to install dependencies on the composer.

Basically this version uses the same file responsible for checking the technologies used in the website, the name of this file is called ```apps.json```

## Always keep ```apps.json``` file updated (icons too)

New technologies are often released every day, so if you want to continue using this code to check which technologies are used on certain websites, I recommend that you constantly update the ```apps.json``` file. You can find it [in this link](https://github.com/AliasIO/Wappalyzer/blob/master/src/apps.json).

Do not forget to update the icons as well, they can be found [in this link](https://github.com/AliasIO/Wappalyzer/tree/master/src/icons)

## How to use this code ?

The first thing you should have are the 2 files called ```Wappalyzer.php``` (That represents the class that download website contents and search for technologies) and ```apps.json``` (The json file that have some instructions to recognize the technologies).

As described in the ```index.php``` file, you only need a few steps:

```
include ('Wappalyzer.php');
$wpp = new Wappalyzer();
$allTecnologies = $wpp->returnTecnologiesFromWebsite('https://google.com');
var_dump($allTecnologies);
```

## Where can I change the path of the ```apps.json``` file ?

In this version you can change the path of the file inside the ```Wappalizer``` class.

```
private $jsonLocation = 'apps.json';
```
