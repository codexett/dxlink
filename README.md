# DXlink (dev)

## Start
docker compose up -d

Open: http://localhost:8080

## Structure
- dashboard/src   UI + API (mock)
- dashboard/src/api/system  summary, fs toggle, control, logs (SSE)
- dashboard/src/api/wifi    scan/status/connect (mock)
- dashboard/src/data        JSON state (ignored by git)

