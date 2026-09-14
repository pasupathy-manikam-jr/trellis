import AVFoundation
import CoreGraphics
import Foundation

// Renders a seamless abstract loop for the Trellis hero: drifting brand-coloured
// blobs over a deep gradient. Motion is driven by whole multiples of 2π so the
// last frame flows back into the first.

let width = 1600, height = 900
let fps: Int32 = 24
let seconds = 12.0
let frameCount = Int(Double(fps) * seconds)
let outURL = URL(fileURLWithPath: CommandLine.arguments[1])
try? FileManager.default.removeItem(at: outURL)

let writer = try AVAssetWriter(outputURL: outURL, fileType: .mp4)
let settings: [String: Any] = [
    AVVideoCodecKey: AVVideoCodecType.h264,
    AVVideoWidthKey: width,
    AVVideoHeightKey: height,
    AVVideoCompressionPropertiesKey: [
        AVVideoAverageBitRateKey: 1_600_000,
        AVVideoProfileLevelKey: AVVideoProfileLevelH264HighAutoLevel,
    ],
]
let input = AVAssetWriterInput(mediaType: .video, outputSettings: settings)
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

struct Blob {
    let rgb: (CGFloat, CGFloat, CGFloat)
    let cx: CGFloat, cy: CGFloat     // centre, as a fraction of the canvas
    let dx: CGFloat, dy: CGFloat     // drift amplitude
    let radius: CGFloat
    let cycles: Double               // whole cycles per loop, so it closes
    let phase: Double
    let alpha: CGFloat
}

// Kept deliberately dim: the hero carries white text, and additive blending
// blows out fast. These are glows on a dark ground, not colour fields.
let blobs = [
    Blob(rgb: (0.137, 0.580, 0.494), cx: 0.28, cy: 0.34, dx: 0.26, dy: 0.20,
         radius: 0.46, cycles: 1, phase: 0.0, alpha: 0.34),        // teal
    Blob(rgb: (0.078, 0.655, 0.922), cx: 0.74, cy: 0.44, dx: 0.24, dy: 0.26,
         radius: 0.40, cycles: 2, phase: 1.9, alpha: 0.26),        // sky
    Blob(rgb: (0.561, 0.369, 0.871), cx: 0.48, cy: 0.82, dx: 0.30, dy: 0.18,
         radius: 0.44, cycles: 1, phase: 3.4, alpha: 0.24),        // violet
    Blob(rgb: (0.961, 0.639, 0.078), cx: 0.66, cy: 0.18, dx: 0.22, dy: 0.24,
         radius: 0.24, cycles: 3, phase: 0.8, alpha: 0.18),        // amber
]

struct Mote {
    let x0: CGFloat, y0: CGFloat
    let speed: CGFloat        // canvas widths travelled per loop; whole numbers only
    let size: CGFloat
    let alpha: CGFloat
    let bob: CGFloat
    let bobCycles: Double
    let phase: Double
}

// Deterministic: the same asset comes out of every run.
var seed: UInt64 = 0x5EED_1234
func rnd() -> CGFloat {
    seed = seed &* 6364136223846793005 &+ 1442695040888963407
    return CGFloat((seed >> 33) % 100_000) / 100_000
}

let motes: [Mote] = (0..<70).map { _ in
    Mote(
        x0: rnd(),
        y0: rnd(),
        speed: [1, 1, 2].randomElement()!,
        size: 0.004 + rnd() * 0.020,
        alpha: 0.10 + rnd() * 0.22,
        bob: 0.02 + rnd() * 0.05,
        bobCycles: Double([1, 2].randomElement()!),
        phase: Double(rnd()) * 6.283
    )
}

func makeBuffer() -> CVPixelBuffer {
    var pb: CVPixelBuffer?
    CVPixelBufferPoolCreatePixelBuffer(nil, adaptor.pixelBufferPool!, &pb)
    return pb!
}

for frame in 0..<frameCount {
    let t = Double(frame) / Double(frameCount)          // 0 ..< 1

    while !input.isReadyForMoreMediaData { usleep(2000) }

    let buffer = makeBuffer()
    CVPixelBufferLockBaseAddress(buffer, [])
    let ctx = CGContext(
        data: CVPixelBufferGetBaseAddress(buffer),
        width: width, height: height, bitsPerComponent: 8,
        bytesPerRow: CVPixelBufferGetBytesPerRow(buffer),
        space: space,
        bitmapInfo: CGImageAlphaInfo.noneSkipFirst.rawValue)!

    // Deep base gradient.
    let base = CGGradient(
        colorsSpace: space,
        colors: [
            CGColor(red: 0.016, green: 0.071, blue: 0.063, alpha: 1),
            CGColor(red: 0.020, green: 0.094, blue: 0.118, alpha: 1),
        ] as CFArray,
        locations: [0, 1])!
    ctx.drawLinearGradient(
        base,
        start: CGPoint(x: 0, y: CGFloat(height)),
        end: CGPoint(x: CGFloat(width), y: 0),
        options: [])

    ctx.setBlendMode(.plusLighter)

    for b in blobs {
        let a = 2 * Double.pi * b.cycles * t + b.phase
        let x = (b.cx + b.dx * CGFloat(cos(a))) * CGFloat(width)
        let y = (b.cy + b.dy * CGFloat(sin(a))) * CGFloat(height)
        let r = b.radius * CGFloat(width) * (1 + 0.10 * CGFloat(sin(a * 1)))

        let g = CGGradient(
            colorsSpace: space,
            colors: [
                CGColor(red: b.rgb.0, green: b.rgb.1, blue: b.rgb.2, alpha: b.alpha),
                CGColor(red: b.rgb.0, green: b.rgb.1, blue: b.rgb.2, alpha: 0),
            ] as CFArray,
            locations: [0, 1])!

        ctx.drawRadialGradient(
            g,
            startCenter: CGPoint(x: x, y: y), startRadius: 0,
            endCenter: CGPoint(x: x, y: y), endRadius: r,
            options: [])
    }

    // Motes drift left-to-right, each covering a whole number of canvas widths
    // over the loop. Drawing every one twice, a width apart, means one slides in
    // as its twin slides out — no pop at the wrap.
    for m in motes {
        let travel = (m.x0 + m.speed * CGFloat(t)).truncatingRemainder(dividingBy: 1)
        let bob = m.bob * CGFloat(sin(2 * Double.pi * m.bobCycles * t + m.phase))
        let y = (m.y0 + bob) * CGFloat(height)
        let r = m.size * CGFloat(width)

        for copy in [travel, travel - 1] {
            let x = copy * CGFloat(width)
            if x < -r || x > CGFloat(width) + r { continue }

            let g = CGGradient(
                colorsSpace: space,
                colors: [
                    CGColor(red: 0.78, green: 0.95, blue: 0.94, alpha: m.alpha),
                    CGColor(red: 0.60, green: 0.88, blue: 0.92, alpha: 0),
                ] as CFArray,
                locations: [0, 1])!

            ctx.drawRadialGradient(
                g,
                startCenter: CGPoint(x: x, y: y), startRadius: 0,
                endCenter: CGPoint(x: x, y: y), endRadius: r,
                options: [])
        }
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
