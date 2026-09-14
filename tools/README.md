# tools

## hero-video.swift

Generates `public/hero.mp4` — the abstract loop behind the splash hero. Written
here rather than downloaded so the asset has a known origin and can be re-tuned
instead of replaced.

```sh
swiftc -O tools/hero-video.swift -o /tmp/herogen && /tmp/herogen public/hero.mp4
```

12s, 1600x900, 24fps, ~1.2 MB. Motion is driven by whole multiples of 2π, so the
last frame flows back into the first and the loop does not jump.

Keep it dark: the hero carries white text, and the blobs blend additively, which
blows out to near-white fast. The `alpha` values are low on purpose.

To regenerate the poster frame (`public/hero.jpg`, also the fallback when a
browser will not play the video), grab frame zero with `AVAssetImageGenerator`.

Replacing this with real footage is just a matter of dropping your own
`public/hero.mp4` in — nothing references this script at runtime.
