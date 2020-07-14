# Wordpress Code Injection Plugin
> Current Version [2.4.4](https://wordpress.org/plugins/code-injection)

This plugin allows you to inject code snippets into the pages.

## Usage
Once the plugin is activated you will see the Code button in the dashboard menu. 
- Create a new code.
- Copy the CID from the list.
- Put the shortcode `[inject id='#']` in your post or page.
- Replace `#` with the CID.

Or

- Drag the CI widget into the desired sidebar area.


### In order to run PHP scripts, follow the instruction below:

- Head to `Settings > General`, In the **Code Injection** section, you will see **Activator Keys**
- Create a strong key.
- Put the Shortcode `[unsafe key='#']` in your post or page, and replace `#` with the key.
- Write your PHP code and close the section by using `[/unsafe]`.

    ```php
    <h1>
    [unsafe key='key-un7la32'] <?php echo "Hello World!"; ?> [/unsafe]
    </h1>
    ```