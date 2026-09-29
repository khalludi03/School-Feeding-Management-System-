#!/bin/bash
while true; do
  STATUS=$(railway status | grep "sfp-web-app:" | awk -F'·' '{print $2}' | xargs)
  if [[ "$STATUS" == https* ]]; then
    echo "Deployment finished and online!"
    break
  else
    echo "Current status: $STATUS"
    sleep 10
  fi
done

echo "Running E2E script..."
node e2e_final_verify.mjs
