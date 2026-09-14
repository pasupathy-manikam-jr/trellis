import AVFoundation
import CoreGraphics
import Foundation

// Trellis hero loop.
//
// The mark is a diamond lattice, so the motion is growth climbing one: light
// travels up the lattice and the nodes it passes light up and fade, the way
// lessons complete along a course. Nothing is decorative for its own sake —
// the lattice is the logo, the upward travel is progress, the nodes are lessons.
//
// The wave crosses a whole number of lattice rows per loop, so the last frame
// flows back into the first.

let width = 1600, height = 900
let fps: Int32 = 24
let seconds = 14.0
let frameCount = Int(Double(fps) * seconds)

let spacing: CGFloat = 96          // lattice pitch
let sweeps = 1.0                   // times the light climbs the full page per loop
let outURL = URL(fileURLWithPath: CommandLine.arguments[1])
try? FileManager.default.removeItem(at: outURL)

let writer = try AVAssetWriter(outputURL: outURL, fileType: .mp4)
let input = AVAssetWriterInput(mediaType: .video, outputSettings: [
    AVVideoCodecKey: AVVideoCodecType.h264,
    AVVideoWidthKey: width,
    AVVideoHeightKey: height,
    AVVideoCompressionPropertiesKey: [
        AVVideoAverageBitRateKey: 2_000_000,
        AVVideoProfileLevelKey: AVVideoProfileLevelH264HighAutoLevel,
    ],
])
input.expectsMediaDataInRealTime = false
let adaptor = AVAssetWriterInputPixelBufferAdaptor(
    assetWriterInput: input,
    sourcePixelBufferAttributes: [
        kCVPixelBufferPixelFormatTypeKey as String: Int(kCVPixelFormatType_32ARGB),
        kCVPixelBufferWidthKey as String: width,
        kCVPixelBufferHeightKey as String: height,
    ])
writer.add(input)
writer.startWriting()
writer.startSession(atSourceTime: .zero)

let space = CGColorSpaceCreateDeviceRGB()
let w = CGFloat(width), h = CGFloat(height)

/// A single soft band. `u` is height up the page (0 bottom, 1 top); `t` drives
/// the climb. Periodic, so the band leaving the top is the one entering the
/// bottom and the loop closes.
func climb(_ u: Double, _ t: Double, width: Double = 0.20) -> CGFloat {
    let x = (u - t * sweeps).truncatingRemainder(dividingBy: 1)
    let p = x < 0 ? x + 1 : x
    let d = min(p, 1 - p)                     // distance to the crest
    return CGFloat(exp(-pow(d / width, 2)))
}

/// Height up the page, 0 at the bottom.
///
/// This bitmap context puts y = 0 at the bottom of the rendered frame, so
/// "up" is simply increasing y. Getting this backwards made the light fall
/// down the trellis instead of climbing it.
func up(_ y: CGFloat) -> Double { Double(y / h) }

for frame in 0..<frameCount {
    let t = Double(frame) / Double(frameCount)

    while !input.isReadyForMoreMediaData { usleep(2000) }

    var pb: CVPixelBuffer?
    CVPixelBufferPoolCreatePixelBuffer(nil, adaptor.pixelBufferPool!, &pb)
    let buffer = pb!
    CVPixelBufferLockBaseAddress(buffer, [])
    let ctx = CGContext(
        data: CVPixelBufferGetBaseAddress(buffer),
        width: width, height: height, bitsPerComponent: 8,
        bytesPerRow: CVPixelBufferGetBytesPerRow(buffer),
        space: space,
        bitmapInfo: CGImageAlphaInfo.noneSkipFirst.rawValue)!

    // Ground.
    let base = CGGradient(colorsSpace: space, colors: [
        CGColor(red: 0.012, green: 0.063, blue: 0.055, alpha: 1),
        CGColor(red: 0.020, green: 0.098, blue: 0.125, alpha: 1),
    ] as CFArray, locations: [0, 1])!
    ctx.drawLinearGradient(base, start: CGPoint(x: 0, y: h), end: CGPoint(x: w, y: 0), options: [])

    ctx.setBlendMode(.plusLighter)

    // Two slow colour washes for depth — teal low, violet high.
    for (rgb, cx, cy, rad, alpha, cyc, ph) in [
        ((0.137, 0.580, 0.494) as (CGFloat, CGFloat, CGFloat), 0.30, 0.30, 0.52, 0.26, 1.0, 0.0),
        ((0.478, 0.333, 0.800), 0.74, 0.72, 0.46, 0.18, 1.0, 3.1),
    ] {
        let a = 2 * Double.pi * cyc * t + ph
        let x = (CGFloat(cx) + 0.10 * CGFloat(cos(a))) * w
        let y = (CGFloat(cy) + 0.08 * CGFloat(sin(a))) * h
        let g = CGGradient(colorsSpace: space, colors: [
            CGColor(red: rgb.0, green: rgb.1, blue: rgb.2, alpha: CGFloat(alpha)),
            CGColor(red: rgb.0, green: rgb.1, blue: rgb.2, alpha: 0),
        ] as CFArray, locations: [0, 1])!
        ctx.drawRadialGradient(g, startCenter: CGPoint(x: x, y: y), startRadius: 0,
                               endCenter: CGPoint(x: x, y: y), endRadius: CGFloat(rad) * w, options: [])
    }

    // --- the lattice -------------------------------------------------------
    // Two families of 45-degree lines make the diamond cells of the mark. Each
    // line is drawn in short segments so the climbing band can brighten just
    // the part of it that the light has reached.
    ctx.setLineCap(.round)

    let diag = spacing * 1.4142
    let lines = Int((w + h) / diag) + 3
    let step: CGFloat = 24                      // segment length along the line

    for family in 0..<2 {
        let rising = family == 0
        for i in -2...lines {
            let c = CGFloat(i) * diag
            let start = rising ? CGPoint(x: c - h, y: 0) : CGPoint(x: c, y: 0)
            let dir: CGFloat = rising ? 1 : -1

            var y: CGFloat = 0
            while y < h {
                let y2 = min(y + step, h)
                let x1 = start.x + dir * y
                let x2 = start.x + dir * y2
                if max(x1, x2) < -step || min(x1, x2) > w + step { y = y2; continue }

                let glow = climb(up((y + y2) / 2), t)
                ctx.setStrokeColor(CGColor(red: 0.45, green: 0.88, blue: 0.82,
                                           alpha: 0.085 + 0.24 * glow))
                ctx.setLineWidth(1.0 + 1.0 * glow)
                ctx.beginPath()
                ctx.move(to: CGPoint(x: x1, y: y))
                ctx.addLine(to: CGPoint(x: x2, y: y2))
                ctx.strokePath()

                y = y2
            }
        }
    }

    // --- nodes -------------------------------------------------------------
    // The lattice vertices. They flare as the light reaches them and fade
    // behind it — lessons completing on the way up.
    var row = 0
    var ny = h + spacing
    while ny > -spacing {
        let offset: CGFloat = row.isMultiple(of: 2) ? 0 : spacing / 2
        var nx = offset - spacing
        while nx < w + spacing {
            let glow = climb(up(ny), t, width: 0.13)

            if glow > 0.015 {
                let r = 2.2 + 17 * glow
                let g = CGGradient(colorsSpace: space, colors: [
                    CGColor(red: 0.82, green: 1.0, blue: 0.95, alpha: 0.05 + 0.52 * glow),
                    CGColor(red: 0.40, green: 0.92, blue: 0.88, alpha: 0),
                ] as CFArray, locations: [0, 1])!
                ctx.drawRadialGradient(g, startCenter: CGPoint(x: nx, y: ny), startRadius: 0,
                                       endCenter: CGPoint(x: nx, y: ny), endRadius: r, options: [])
            }
            nx += spacing
        }
        ny -= spacing / 2
        row += 1
    }

    CVPixelBufferUnlockBaseAddress(buffer, [])
    adaptor.append(buffer, withPresentationTime: CMTime(value: CMTimeValue(frame), timescale: fps))
}

input.markAsFinished()
let done = DispatchSemaphore(value: 0)
writer.finishWriting { done.signal() }
done.wait()

if writer.status == .completed {
    print("wrote \(outURL.path)")
} else {
    print("failed: \(writer.error?.localizedDescription ?? "unknown")")
    exit(1)
}
