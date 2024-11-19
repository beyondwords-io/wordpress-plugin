#   wp-config.php

To override default behaviour, the following constants can be defined in
`wp-config.php`.

##  BEYONDWORDS_AUTO_SYNC_SETTINGS

Should we auto-sync the settings to/from the BeyondWords dashboard?
Defaults to `true`.

```php
define('BEYONDWORDS_AUTO_SYNC_SETTINGS', false);
```

##  BEYONDWORDS_AUTOREGENERATE

Should we autoregenerate the audio when an existing Post is updated?
Defaults to `true`.

```php
define('BEYONDWORDS_AUTOREGENERATE', false);
```

##  BEYONDWORDS_PLAYER_INLINE_SCRIPT_TAG

Use the recommended BeyondWords inline player script.
Defaults to `false`.
From plugin version `6.0` this will default to `true`.

```php
define('BEYONDWORDS_PLAYER_INLINE_SCRIPT_TAG', true);
```
