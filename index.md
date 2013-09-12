---
layout: default
---

Picturo is a simple flat file photo gallery running. No admin, you just need to drop your pictures in the `content` to publish your pictures.
Picturo is heavily inspired by [Pico](http://pico.dev7studios.com/), but it's for photos.

## Requirements

To run Picturo you will need **PHP 5.2.4+** and **GD Library** on your server. If you're running Apache you will also require `mod_rewrite` to be enabled.

## Installation

1. First [download Picturo ](https://github.com/jbleuzen/Picturo/zipball/master) and extract it on your server.
2. Change ownership and permission of the `cache` folder to the user of your web server set permissions to 777 so you'll be able to delete cache if needed.
3. Upload your folders of pictures into `content`.
4. Customize settings by editing `config.php` in the root folder of Picturo. To override a setting simply uncomment it in `config.php` and set your custom value.

## Privatize your site

Picturo can keep your galleries private, you just have to edit `config.php` and set `private` variable to true and add a user. For exemple, you can create an "admin" user with "admin" as passwords :

{% highlight php %}
$config['private'] = true;
$config['private_pass']['admin'] = 'd033e22ae348aeb5660fc2140aec35850c4da997';
{% endhighlight %}

Passwords are SHA-1 strings, you can generate your passwords online [here](http://www.sha1-online.com/).

## Add galleries

Your photos must be stored in `content` folder, if you want to add a gallery you just need to create a folder within your `content` folder.

Galleries thumbnail are generated with the first picture found in the folder.

## Create your theme

Picturo supports themes with [Twig](http://twig.sensiolabs.org/) as templating engine they are located in the `themes` folder.

All themes must include two files :

 * gallery.html
 * detail.html

If you want to keep your [galleries private](/#privatize_your_site), you will need a third file "login.html".

Each file will have a basic set of variable, plus specific variable. 

Default variables, available for all files : 
 
 * \{\{ base_url \}\} : The URL of your site
 * \{\{ theme_url \}\} : The URL of your active theme
 * \{\{ site_title \}\} : The title of your website
 * \{\{ username \}\} : The logged in username (if you use private gallery)

Variables for `gallery.html`, which will list all folders and pictures :

 * \{\{ url \}\} : The current url
 * \{\{ breadcrumb \}\} : An hash containing a splitted array of URL. Keys are URL and values are folders name
 * \{\{ folders \}\} : An array containing folder objects for current url. Each folder has 3 attributes (url, thumbnail, name)
 * \{\{ images \}\} : An array containing image objects for current url. Each image has 5 attributes (url, thumbnail, name, width, height)
 * \{\{ page_count \}\} : The total count of pages for current url.
 * \{\{ current_page \}\} : The current page

Variables for `detail.html`, which will display one picture : 

* \{\{ breadcrumb \}\} : An hash containing a splitted array of URL. Keys are URL and values are folders name
* \{\{ image_url \}\} : The URL of the current image
* \{\{ image_previous_url \}\} : The URL of the previous image
* \{\{ image_next_url \}\} : The URL of the next image

Variables for `login.html`, which will display the login form : 

 * \{\{ login_error \}\} : Tell if login attempt was valid or not
 * \{\{ username \}\} : The username entered in login form input

I recommend to check the `default` theme and use [Twig template](http://twig.sensiolabs.org/doc/templates.html#template-inheritance) for easy theming.

## Contribute

Help make Picturo better by checking out the [GitHub repoistory](https://github.com/jbleuzen/Picturo) and submitting pull requests.
If you find a bug please report it on the [issues page](https://github.com/jbleuzen/Picturo/issues).

