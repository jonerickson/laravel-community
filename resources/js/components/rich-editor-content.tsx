import { cn } from '@/lib/utils';

interface RichEditorContentProps extends React.HTMLAttributes<HTMLDivElement> {
    content: string;
    className?: string;
}

export default function RichEditorContent({ content, className, ...props }: RichEditorContentProps) {
    return (
        <div
            // prettier-ignore
            className={cn(
                "prose prose-sm max-w-none",
                "[&_p]:text-sm [&_p]:mt-2",
                "[&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_a]:decoration-primary [&_a]:underline-offset-2",
                "dark:[&_a]:text-blue-400 dark:[&_a]:decoration-blue-400",

                "[&_pre]:relative [&_pre]:my-4 [&_pre]:overflow-x-auto [&_pre]:rounded-md [&_pre]:border [&_pre]:border-border [&_pre]:bg-muted [&_pre]:p-4 [&_pre]:font-mono [&_pre]:text-sm [&_pre]:text-muted-foreground",
                "[&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:font-mono [&_pre_code]:text-foreground",

                "[&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:bg-muted [&_blockquote]:py-4 [&_blockquote]:text-muted-foreground [&_blockquote]:border-border [&_blockquote]:my-4",

                "[&_h1]:text-2xl [&_h2]:text-xl [&_h3]:text-lg [&_h4]:text-base [&_h5]:text-sm [&_h6]:text-xs",
                "[&_h1]:font-semibold [&_h2]:font-semibold [&_h3]:font-semibold [&_h4]:font-medium [&_h5]:font-medium [&_h6]:font-medium",
                "[&_h1]:mt-4 [&_h2]:mt-4 [&_h3]:mt-4 [&_h4]:mt-4 [&_h5]:mt-4 [&_h6]:mt-4",

                "[&_ul]:list-disc [&_ul]:ml-6 [&_ul]:my-2",
                "[&_ol]:list-decimal [&_ol]:ml-6 [&_ol]:my-4",
                className
            )}
            dangerouslySetInnerHTML={{ __html: content }}
            {...props}
        />
    );
}
