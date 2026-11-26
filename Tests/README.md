# jEasyUI facade tests

## Widget setup storage

Serve the installation's `vendor/` directory on a loopback-only static server and open
[`JavaScript/SetupStorage.html`](JavaScript/SetupStorage.html). Run this in the browser console:

```javascript
await (await import('./EuiSetupStorage.mjs')).runEuiSetupStorageRegression()
```

The test uses a temporary database and verifies legacy migration, installation isolation,
resets, concurrent operations and compatibility with UI5's schema without touching existing
preferences.
