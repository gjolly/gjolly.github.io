#!/bin/bash

# purge cloudflare cache for gauthier.uk and
# gauthierjolly.com

token="n14vA6X734jSM1oDAxQt7l4zIpJJ9nfjZhii6WqH"
gauthierjolly="78175c8445169bae496a0102b03d81ae"
gauthieruk="7acdd35236c2028e360f36ecbcf3be0f"

for zone in $gauthieruk $gauthierjolly; do
  curl -X POST "https://api.cloudflare.com/client/v4/zones/$zone/purge_cache" \
       -H "Authorization: Bearer $token" \
       -H "Content-Type: application/json" \
       --data '{"purge_everything":true}'
done
