# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_BEARER_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtain a token via <code>POST /api/auth/register</code>, <code>POST /api/auth/login</code>, or <code>POST /api/auth/guest</code>.
