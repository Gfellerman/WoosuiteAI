# WooSuite AI Tests

Since the development environment may not have a running WordPress instance or PHP CLI configured with WP core, we use **Mock Tests** to verify the logic of our classes.

## API Logic Test
`test_api_logic.php` verifies that the `WooSuite_Api` class correctly maps JSON parameters to WordPress `update_post_meta` calls.

### Usage
If you have PHP CLI installed:
```bash
php tests/test_api_logic.php
```
(Note: You might need to adjust paths if running from root).
