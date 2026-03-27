# Introduction

Backend API for the Revive app — real-time prayer and Bible study sessions.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

    This documentation covers all endpoints for the **Revive** mobile app.

    **Base URL:** `http://localhost/api`

    **Authentication:** Most endpoints require a Bearer token obtained from `/api/auth/register`, `/api/auth/login`, or `/api/auth/guest`. Pass it as:
    ```
    Authorization: Bearer {token}
    ```

    <aside>Use the Try It Out button on each endpoint to test requests directly from the browser.</aside>

