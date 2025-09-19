export default function HeadingLarge({ title, description }: { title: string; description?: string }) {
    return (
        <div className="max-4-xl mx-auto mb-8 space-y-4">
            <h2 className="text-3xl font-semibold tracking-tight md:text-4xl">{title}</h2>
            {description && <p className="text-lg text-muted-foreground">{description}</p>}
        </div>
    );
}
