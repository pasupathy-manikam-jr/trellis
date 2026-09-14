/**
 * Hero background, in order of preference:
 *   1. public/hero.mp4  — drop the file in, no config
 *   2. public/hero.jpg, or the newest course cover
 *   3. an animated gradient mesh, which needs no asset at all
 *
 * The mesh is decorative and stills itself for prefers-reduced-motion.
 */
export function HeroBackdrop({ video, image }: { video?: string | null; image?: string | null }) {
    return (
        <div className="absolute inset-0 -z-10 overflow-hidden">
            {video ? (
                <video
                    autoPlay
                    muted
                    loop
                    playsInline
                    poster={image ?? undefined}
                    className="size-full object-cover"
                >
                    <source src={video} />
                </video>
            ) : image ? (
                <img src={image} alt="" className="size-full object-cover" />
            ) : (
                <Mesh />
            )}

            {/* Keeps text legible over anything behind it. */}
            <div className="absolute inset-0 bg-gradient-to-br from-black/85 via-black/65 to-black/45" />
        </div>
    );
}

function Mesh() {
    return (
        <div className="size-full bg-[linear-gradient(140deg,hsl(168_70%_9%),hsl(190_60%_14%))]">
            <div
                className="animate-drift absolute -top-1/4 -left-[10%] size-[55vw] rounded-full opacity-60 blur-3xl"
                style={{ background: 'radial-gradient(circle, var(--brand-teal), transparent 70%)' }}
            />
            <div
                className="animate-drift absolute top-1/3 right-[-5%] size-[45vw] rounded-full opacity-50 blur-3xl"
                style={{
                    background: 'radial-gradient(circle, var(--brand-sky), transparent 70%)',
                    animationDelay: '-7s',
                }}
            />
            <div
                className="animate-drift absolute -bottom-1/4 left-1/4 size-[50vw] rounded-full opacity-40 blur-3xl"
                style={{
                    background: 'radial-gradient(circle, var(--brand-violet), transparent 70%)',
                    animationDelay: '-14s',
                }}
            />
            <div
                className="animate-drift absolute top-[10%] left-1/2 size-[30vw] rounded-full opacity-30 blur-3xl"
                style={{
                    background: 'radial-gradient(circle, var(--brand-amber), transparent 70%)',
                    animationDelay: '-4s',
                }}
            />
        </div>
    );
}
