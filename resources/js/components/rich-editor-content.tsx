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
                "prose prose-sm max-w-none wrap-anywhere text-wrap",
                "[&_p]:text-sm [&_p]:mt-2 [&_p]:wrap-anywhere [&_p]:text-wrap",
                "[&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_a]:decoration-primary [&_a]:underline-offset-2 [&_a]:wrap-anywhere [&_a]:text-wrap",
                "dark:[&_a]:text-blue-400 dark:[&_a]:decoration-blue-400",

                "[&_pre]:relative [&_pre]:my-4 [&_pre]:overflow-x-auto [&_pre]:rounded-md [&_pre]:border [&_pre]:border-border [&_pre]:bg-muted [&_pre]:p-4 [&_pre]:font-mono [&_pre]:text-sm [&_pre]:text-muted-foreground",
                "[&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:font-mono [&_pre_code]:text-foreground [&_pre_code]:wrap-anywhere [&_pre_code]:text-wrap",

                "[&_blockquote>p:first-child]:mt-0 [&_blockquote]:border-l-4 [&_blockquote]:p-4 [&_blockquote]:italic [&_blockquote]:bg-muted [&_blockquote]:text-muted-foreground [&_blockquote]:border-border [&_blockquote]:my-4",

                "[&_h1]:text-2xl [&_h2]:text-xl [&_h3]:text-lg [&_h4]:text-base [&_h5]:text-sm [&_h6]:text-xs",
                "[&_h1]:font-semibold [&_h2]:font-semibold [&_h3]:font-semibold [&_h4]:font-medium [&_h5]:font-medium [&_h6]:font-medium",
                "[&_h1]:mt-4 [&_h2]:mt-4 [&_h3]:mt-4 [&_h4]:mt-4 [&_h5]:mt-4 [&_h6]:mt-4",

                "[&_ul]:list-disc [&_ul]:ml-6 [&_ul]:my-2",
                "[&_ol]:list-decimal [&_ol]:ml-6 [&_ol]:my-4",

                "[&_hr]:my-4 [&_hr]:border-border",

                "[&_table]:my-4 [&_table]:w-full [&_table]:border-collapse [&_table]:overflow-hidden [&_table]:rounded-md [&_table]:border [&_table]:border-border",
                "[&_th]:border [&_th]:border-border [&_th]:bg-muted [&_th]:px-4 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold",
                "[&_td]:border [&_td]:border-border [&_td]:px-4 [&_td]:py-2",
                className
            )}
            dangerouslySetInnerHTML={{ __html: content }}
            {...props}
        />
    );
}
