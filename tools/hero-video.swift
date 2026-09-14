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
    Blob(rgb: (0.137, 0.580, 0.494), cx: 0.20, cy: 0.28, dx: 0.10, dy: 0.08,
         radius: 0.46, cycles: 1, phase: 0.0, alpha: 0.30),        // teal
    Blob(rgb: (0.078, 0.655, 0.922), cx: 0.80, cy: 0.42, dx: 0.09, dy: 0.11,
         radius: 0.40, cycles: 1, phase: 1.9, alpha: 0.22),        // sky
    Blob(rgb: (0.561, 0.369, 0.871), cx: 0.46, cy: 0.88, dx: 0.12, dy: 0.07,
         radius: 0.44, cycles: 1, phase: 3.4, alpha: 0.20),        // violet
    Blob(rgb: (0.961, 0.639, 0.078), cx: 0.70, cy: 0.14, dx: 0.08, dy: 0.10,
         radius: 0.24, cycles: 2, phase: 0.8, alpha: 0.14),        // amber
]

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
