import { CodeEditor } from '@/components/code-editor';
import { Toaster } from '@/components/ui/sonner';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { File } from '@/types';
import { useForm } from '@inertiajs/react';

interface EditorProps {
    page: App.Data.PageData;
}

export default function Editor({ page }: EditorProps) {
    useFlashMessages();

    const { post, transform } = useForm({
        files: '',
    });

    const handleOnSave = (files: File[]) => {
        transform(() => ({
            files: files,
        }));

        post(route('admin.pages.store', { page: page.id }));
    };

    return (
        <main className="h-screen w-full">
            <CodeEditor html={page.htmlContent} js={page.jsContent} css={page.cssContent} onSave={handleOnSave} />
            <Toaster />
        </main>
    );
}
