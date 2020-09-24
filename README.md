# Wordpress Code Injection Plugin

This plugin allows you to inject code snippets into the pages.

## Usage
Once the plugin is activated you will see the Code button in the dashboard menu. 
- Create a new code.
- Copy the CID from the list.
- Put the Shortcode `[inject id='#']` in your post or page.
- Replace `#` with the CID.

Or
- Drag the CI widget into the desired sidebar area.

>**Note:** You can find the **Code Options** in the editor page.

### In order to run PHP scripts, follow the instruction below:

Head to `Settings > General`, In the **Code Injection** section, you will see **Activator Keys**

- Create a strong key.
- Put the Shortcode `[unsafe key='#']` in your post or page, and replace `#` with the key you created before.
- Write your PHP script and close unsafe section by using `[/unsafe]`.

    ```php
    [unsafe key='key-un7la32'] <?php phpinfo(); ?> [/unsafe]
    ```