# Wordpress Code Injection Plugin
> Current Version [2.4.0](https://github.com/Rmanaf/wp-code-injection) - [Change logs](https://github.com/Rmanaf/wp-code-injection/blob/master/CHANGELOG.md)

Allows You to inject code snippets into the pages by just using the Wordpress shortcode

## Usage
Once the plugin activated you will see the Code button in the dashboard menu. 
- Create a new code, that includes HTML, CSS, and javascript.
- Copy the generated ID, from the Codes list.
- Put the Shortcode `[inject id='code-#']` in your post or page content, and replace `#` with the ID.

Or
- Place the Code Injection widget into the desired sidebar.
- Find and select the Code ID from the Widget drop-down list.

### In order to run PHP codes, follow the instruction below

Go to `Settings > General`, In the **Code Injection** section, You will see **Activator Keys**

- Create a strong key that includes digits and characters
- Put the Shortcode `[unsafe key='#']` in Your post or page content, and replace `#` with the Key You've created before.
- Write Your PHP code and close unsafe section by using `[/unsafe]`

## Bug & Issues Reporting
If you faced any issues, please tell us on [Github](https://github.com/Rmanaf/wp-code-injection/issues/new)