# tools

## hero-video.swift

Generates `public/hero.mp4` — the loop behind the splash hero. Written here
rather than downloaded so the asset has a known origin and can be re-tuned
instead of replaced.

**What it shows, and why.** The mark is a diamond lattice, so the hero is one:
light climbs the trellis, and the vertices it reaches flare and fade behind it.
Growth along structure — which is the product. Lessons completing in order up a
course. An earlier version drifted anonymous bokeh, which looked like any stock
tech background and said nothing; that is the thing to avoid if you re-tune it.

```sh
swiftc -O tools/hero-video.swift -o /tmp/herogen && /tmp/herogen public/hero.mp4
```

14s, 1600x900, 24fps, ~2.9 MB. Motion is driven by whole multiples of 2π, so the
last frame flows back into the first and the loop does not jump.

This bitmap context has **y = 0 at the bottom** of the rendered frame, so "up
the page" is increasing y. Getting that backwards makes the light fall down the
trellis instead of climbing it, which inverts the whole meaning — check a couple
of frames after any change to `up()`.

Keep it dark and keep the band soft: the hero carries white text over the lower
left, and everything blends additively, which blows out fast. A tight, bright
band reads as chase lights rather than growth — `sweeps`, the `width` arguments
to `climb()`, and the node alpha are all low on purpose.

To regenerate the poster frame (`public/hero.jpg`, also the fallback when a
browser will not play the video), grab frame zero with `AVAssetImageGenerator`.

Replacing this with real footage is just a matter of dropping your own
`public/hero.mp4` in — nothing references this script at runtime.
