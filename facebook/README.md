# Facebook Connector

Running this first requires creating a Web Based application here => [Facebook Developers Console](https://developers.facebook.com/apps).

After that, replace `APP_ID` and `APP_SECRET` with the appropriate value.

Than run this example from the root folder like this:

```bash
php -S 0.0.0.0:8000
```
Comments are saved into a database now. The database is created if it isn't present. 

Creds are managed through `facebook_keys.yaml` file which looks like this:

```yaml
app_id:
    "1235"
app_secret:
    "67899"
```
