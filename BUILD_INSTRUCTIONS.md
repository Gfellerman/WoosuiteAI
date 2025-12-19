# Building WooSuite AI

WooSuite AI uses a hybrid architecture with a PHP backend and a React frontend (for the dashboard). This guide explains how to build the project correctly.

## Prerequisites

*   Node.js (v18+) and npm
*   PHP (v7.4+)

## Development Workflow

1.  **Install Dependencies:**
    ```bash
    npm install
    ```

2.  **Start Development Server:**
    ```bash
    npm run dev
    ```
    *Note: Since this is embedded in WordPress, `npm run dev` is mostly for checking compilation. You typically need to build to see changes in WP Admin.*

3.  **Build for Production:**
    To generate the JavaScript and CSS files that WordPress enqueues, you **must** run:
    ```bash
    npm run build
    ```
    This command compiles the React code into `assets/woosuite-app.js` and `assets/woosuite-app.css`.

4.  **Deployment / Zipping (CRITICAL):**
    When packaging the plugin, you **must** verify that the `woosuite-ai.zip` file is up-to-date with your latest changes.

    **ALWAYS run this command before submitting:**
    ```bash
    ./build_plugin.sh
    ```
    This script automatically installs dependencies, builds the React app, and creates the zip file with the correct exclusions.

    **Verification Step:**
    Run `ls -l woosuite-ai.zip` to confirm the timestamp matches the current time. Do not assume the file is updated automatically.

## Architecture Notes

*   **PHP (`includes/`):** Handles the WordPress menu, enqueueing scripts, and the REST API.
*   **React (`src/`, `components/`):** The dashboard UI.
*   **Integration:** `includes/class-woosuite-admin.php` enqueues the React assets and passes a global `woosuiteData` object (containing the API nonce and URL) to the frontend.
