LeekSync
========

Command line application (powered by [Symfony](https://symfony.com/)) to sync local IA files and [LeekWars](http://leekwars.com)

To use this application, you need to have [PHP](http://php.net/) and [Composer](https://getcomposer.org/doc/00-intro.md) installed on your PC.

Once you have installed these dependencies, clone (or download) this project. and follow those steps

## Fetching your scripts

```bash
bin/console app:fetch
```

You will be prompted for your account and password. 

![Prompt preview](./doc/password.png)

> These are not stored but if you don't trust me (and you should never trust anyone on the internet), you can review my code. 
> This we reassure you and you may have input for improvements :D

Once you have entered you credentials, you should see this confirmation message :

![Fetch success preview](./doc/fetch-success.png)

And all your scripts should be in the `scripts` folder inside this project.

> Note : This folder will be customizable in future version

## Begin to work

```bash
bin/console app:watch
```

Once again put your credentials (If you find it annoying, see [Store credentials](#store-credentials)), you should see this message: 

![Watch start preview](./doc/watch-start.png)

Start editing you files, save and ... tadaa !

![Sync success preview](./doc/sync-success.png)

If your code is invalid, the output will show it

![Sync failure preview](./doc/sync-failure.png)

Here, I have a missing `;` on line 84

## Store credentials

If you are annoyed by typing your credentials all the time, you can store them in the `.env` file at the root of the project.
