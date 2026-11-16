## Run

```bash
docker compose up -d --build --wait
```

The application runs at http://localhost:8080

If port 8080 is already in use, pick another one:

```bash
WEB_PORT=8081 docker compose up -d --build --wait
```

## Stop

```bash
docker compose down
```
