import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useApiRequest } from '@/hooks/use-api-request';
import Emoji, { gitHubEmojis } from '@tiptap/extension-emoji';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import { EditorContent, useEditor, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    Heading,
    Heading1,
    Heading2,
    Heading3,
    Heading4,
    Heading5,
    Heading6,
    ImageIcon,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    LoaderCircle,
    MoreHorizontal,
    Quote,
    Redo,
    Smile,
    Strikethrough,
    Underline,
    Undo,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { ResizableImage } from 'tiptap-extension-resizable-image';
import 'tiptap-extension-resizable-image/styles.css';
import { route } from 'ziggy-js';

interface RichTextEditorProps {
    content: string;
    onChange: (content: string) => void;
    placeholder?: string;
    className?: string;
}

interface LinkDialogProps {
    editor: Editor | null;
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
}

function LinkDialog({ editor, isOpen, onOpenChange }: LinkDialogProps) {
    const [url, setUrl] = useState('');
    const previousUrl = editor?.getAttributes('link').href || '';

    useEffect(() => {
        if (isOpen) {
            setUrl(previousUrl);
        }
    }, [isOpen, previousUrl]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editor) return;

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        } else {
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        }

        onOpenChange(false);
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogContent onOpenAutoFocus={(e) => e.preventDefault()}>
                <DialogHeader>
                    <DialogTitle>Add link</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 pb-4">
                    <div className="grid gap-2">
                        <Input
                            id="url"
                            value={url}
                            onChange={(e) => setUrl(e.target.value)}
                            placeholder="https://example.com"
                            autoFocus
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    handleSubmit(e);
                                }
                            }}
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={handleSubmit}>
                        {url === '' ? 'Remove link' : previousUrl ? 'Update link' : 'Insert link'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface EmojiDialogProps {
    editor: Editor | null;
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
}

interface ImageDialogProps {
    editor: Editor | null;
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
}

function ImageDialog({ editor, isOpen, onOpenChange }: ImageDialogProps) {
    const [imageUrl, setImageUrl] = useState('');
    const [altText, setAltText] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const { loading, execute } = useApiRequest<App.Data.FileData>();

    useEffect(() => {
        if (isOpen) {
            setImageUrl('');
            setAltText('');
            setFile(null);
        }
    }, [isOpen]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            setFile(selectedFile);
            setImageUrl('');
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!editor) return;

        if (file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('visibility', 'public');

            const response = await execute({
                url: route('api.file.store'),
                method: 'POST',
                data: formData,
                config: {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                },
            });

            if (response?.url) {
                editor
                    .chain()
                    .focus()
                    .setResizableImage({
                        src: response.url,
                        alt: altText || file.name,
                    })
                    .run();
                onOpenChange(false);
            }
        } else if (imageUrl) {
            editor
                .chain()
                .focus()
                .setResizableImage({
                    src: imageUrl,
                    alt: altText || 'Image',
                })
                .run();
            onOpenChange(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogContent onOpenAutoFocus={(e) => e.preventDefault()}>
                <DialogHeader>
                    <DialogTitle>Insert image</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 pb-4">
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Upload image</label>
                        <Input type="file" accept="image/*" onChange={handleFileChange} disabled={loading} />
                    </div>
                    <div className="text-center text-sm text-muted-foreground">or</div>
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Image URL</label>
                        <Input
                            value={imageUrl}
                            onChange={(e) => setImageUrl(e.target.value)}
                            placeholder="https://example.com/image.jpg"
                            disabled={!!file || loading}
                        />
                    </div>
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Alt text (optional)</label>
                        <Input value={altText} onChange={(e) => setAltText(e.target.value)} placeholder="Describe the image" disabled={loading} />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={handleSubmit} disabled={(!file && !imageUrl) || loading}>
                        {loading && <LoaderCircle className="animate-spin" />}
                        {loading ? 'Uploading...' : 'Insert image'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function EmojiDialog({ editor, isOpen, onOpenChange }: EmojiDialogProps) {
    const emojis = ['😀', '😂', '😍', '🤔', '👍', '👎', '❤️', '🔥', '💯', '🎉', '😢', '😡', '🤷‍♂️', '🙈', '💪'];

    const insertEmoji = (emoji: string) => {
        if (!editor) return;
        editor.chain().focus().insertContent(emoji).run();
        onOpenChange(false);
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Insert emoji</DialogTitle>
                </DialogHeader>
                <div className="grid grid-cols-5 gap-2 pb-4">
                    {emojis.map((emoji) => (
                        <Button
                            key={emoji}
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => insertEmoji(emoji)}
                            className="size-12 text-xl hover:bg-muted"
                        >
                            {emoji}
                        </Button>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function ToolbarButton({
    action,
    icon: Icon,
    isActive,
    disabled,
}: {
    action: () => void;
    icon: React.ComponentType<{ className?: string }>;
    isActive?: boolean;
    disabled?: boolean;
}) {
    return (
        <Button type="button" variant="ghost" size="sm" onClick={action} className={isActive ? 'bg-muted' : ''} disabled={disabled}>
            <Icon className="size-4" />
        </Button>
    );
}

function ToolbarSeparator() {
    return <div className="mx-1 h-6 w-px bg-border" />;
}

export function RichTextEditor({ content, onChange, placeholder = 'Start typing...', className }: RichTextEditorProps) {
    const [linkDialogOpen, setLinkDialogOpen] = useState(false);
    const [emojiDialogOpen, setEmojiDialogOpen] = useState(false);
    const [imageDialogOpen, setImageDialogOpen] = useState(false);

    const editor = useEditor({
        extensions: [
            StarterKit,
            Placeholder.configure({
                placeholder,
            }),
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Emoji.configure({
                emojis: gitHubEmojis,
                enableEmoticons: true,
            }),
            ResizableImage.configure({
                defaultWidth: 200,
                defaultHeight: 200,
            }),
        ],
        content,
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
    });

    useEffect(() => {
        if (editor && content !== editor.getHTML()) {
            editor.commands.setContent(content);
        }
    }, [editor, content]);

    if (!editor) {
        return null;
    }

    return (
        <>
            <div className={`relative rounded-md border border-input bg-background ${className}`}>
                <div className="flex items-center gap-1 border-b p-2">
                    <ToolbarButton action={() => editor.chain().focus().toggleBold().run()} icon={Bold} isActive={editor.isActive('bold')} />
                    <ToolbarButton action={() => editor.chain().focus().toggleItalic().run()} icon={Italic} isActive={editor.isActive('italic')} />
                    <ToolbarButton
                        action={() => editor.chain().focus().toggleStrike().run()}
                        icon={Strikethrough}
                        isActive={editor.isActive('strike')}
                    />
                    <ToolbarButton
                        action={() => editor.chain().focus().toggleUnderline().run()}
                        icon={Underline}
                        isActive={editor.isActive('underline')}
                    />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button type="button" variant="ghost" size="sm">
                                <Heading className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
                                className="flex items-center gap-2"
                            >
                                <Heading1 className="size-4" />
                                Heading 1
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                                className="flex items-center gap-2"
                            >
                                <Heading2 className="size-4" />
                                Heading 2
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
                                className="flex items-center gap-2"
                            >
                                <Heading3 className="size-4" />
                                Heading 3
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => editor.chain().focus().toggleHeading({ level: 4 }).run()}
                                className="flex items-center gap-2"
                            >
                                <Heading4 className="size-4" />
                                Heading 4
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => editor.chain().focus().toggleHeading({ level: 5 }).run()}
                                className="flex items-center gap-2"
                            >
                                <Heading5 className="size-4" />
                                Heading 5
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => editor.chain().focus().toggleHeading({ level: 6 }).run()}
                                className="flex items-center gap-2"
                            >
                                <Heading6 className="size-4" />
                                Heading 6
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <div className="hidden items-center gap-1 md:flex">
                        <ToolbarSeparator />
                        <ToolbarButton
                            action={() => editor.chain().focus().toggleBulletList().run()}
                            icon={List}
                            isActive={editor.isActive('bulletList')}
                        />
                        <ToolbarButton
                            action={() => editor.chain().focus().toggleOrderedList().run()}
                            icon={ListOrdered}
                            isActive={editor.isActive('orderedList')}
                        />
                        <ToolbarButton
                            action={() => editor.chain().focus().toggleBlockquote().run()}
                            icon={Quote}
                            isActive={editor.isActive('blockquote')}
                        />
                        <ToolbarSeparator />
                        <ToolbarButton
                            action={() => editor.chain().focus().setTextAlign('left').run()}
                            icon={AlignLeft}
                            isActive={editor.isActive({ textAlign: 'left' })}
                        />
                        <ToolbarButton
                            action={() => editor.chain().focus().setTextAlign('center').run()}
                            icon={AlignCenter}
                            isActive={editor.isActive({ textAlign: 'center' })}
                        />
                        <ToolbarButton
                            action={() => editor.chain().focus().setTextAlign('right').run()}
                            icon={AlignRight}
                            isActive={editor.isActive({ textAlign: 'right' })}
                        />
                        <ToolbarButton
                            action={() => editor.chain().focus().setTextAlign('justify').run()}
                            icon={AlignJustify}
                            isActive={editor.isActive({ textAlign: 'justify' })}
                        />
                        <ToolbarSeparator />
                        <ToolbarButton action={() => setLinkDialogOpen(true)} icon={LinkIcon} isActive={editor.isActive('link')} />
                        <ToolbarButton action={() => setImageDialogOpen(true)} icon={ImageIcon} />
                        <ToolbarButton action={() => setEmojiDialogOpen(true)} icon={Smile} />
                        <ToolbarSeparator />
                        <ToolbarButton
                            action={() => editor.chain().focus().undo().run()}
                            icon={Undo}
                            disabled={!editor.can().chain().focus().undo().run()}
                        />
                        <ToolbarButton
                            action={() => editor.chain().focus().redo().run()}
                            icon={Redo}
                            disabled={!editor.can().chain().focus().redo().run()}
                        />
                        <ToolbarSeparator />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button" variant="ghost" size="sm">
                                    <MoreHorizontal className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={() => editor.chain().focus().toggleCodeBlock().run()} className="flex items-center gap-2">
                                    <Code className="size-4" />
                                    Code Block
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div className="ml-auto md:hidden">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button" variant="ghost" size="sm">
                                    <MoreHorizontal className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48">
                                <DropdownMenuItem onClick={() => editor.chain().focus().toggleBulletList().run()} className="flex items-center gap-2">
                                    <List className="size-4" />
                                    Bullet List
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().toggleOrderedList().run()}
                                    className="flex items-center gap-2"
                                >
                                    <ListOrdered className="size-4" />
                                    Numbered List
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => editor.chain().focus().toggleBlockquote().run()} className="flex items-center gap-2">
                                    <Quote className="size-4" />
                                    Quote
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().setTextAlign('left').run()}
                                    className="flex items-center gap-2"
                                >
                                    <AlignLeft className="size-4" />
                                    Align Left
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().setTextAlign('center').run()}
                                    className="flex items-center gap-2"
                                >
                                    <AlignCenter className="size-4" />
                                    Align Center
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().setTextAlign('right').run()}
                                    className="flex items-center gap-2"
                                >
                                    <AlignRight className="size-4" />
                                    Align Right
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().setTextAlign('justify').run()}
                                    className="flex items-center gap-2"
                                >
                                    <AlignJustify className="size-4" />
                                    Justify
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => setLinkDialogOpen(true)} className="flex items-center gap-2">
                                    <LinkIcon className="size-4" />
                                    Insert Link
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => setImageDialogOpen(true)} className="flex items-center gap-2">
                                    <ImageIcon className="size-4" />
                                    Insert Image
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => setEmojiDialogOpen(true)} className="flex items-center gap-2">
                                    <Smile className="size-4" />
                                    Insert Emoji
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().undo().run()}
                                    disabled={!editor.can().chain().focus().undo().run()}
                                    className="flex items-center gap-2"
                                >
                                    <Undo className="size-4" />
                                    Undo
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => editor.chain().focus().redo().run()}
                                    disabled={!editor.can().chain().focus().redo().run()}
                                    className="flex items-center gap-2"
                                >
                                    <Redo className="size-4" />
                                    Redo
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => editor.chain().focus().toggleCodeBlock().run()} className="flex items-center gap-2">
                                    <Code className="size-4" />
                                    Code Block
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
                <div className="min-h-[150px] cursor-text p-3" onClick={() => editor?.chain().focus().run()}>
                    <EditorContent
                        editor={editor}
                        className="prose prose-sm max-w-none focus-within:outline-none [&_.ProseMirror]:min-h-[120px] [&_.ProseMirror]:cursor-text [&_.ProseMirror]:border-none [&_.ProseMirror]:outline-none [&_.ProseMirror_a]:cursor-pointer [&_.ProseMirror_a]:text-blue-600 [&_.ProseMirror_a]:underline [&_.ProseMirror_a]:decoration-blue-600 [&_.ProseMirror_a]:underline-offset-2 dark:[&_.ProseMirror_a]:text-blue-400 dark:[&_.ProseMirror_a]:decoration-blue-400 [&_.ProseMirror_blockquote]:my-4 [&_.ProseMirror_blockquote]:border-l-4 [&_.ProseMirror_blockquote]:border-border [&_.ProseMirror_blockquote]:bg-muted [&_.ProseMirror_blockquote]:p-4 [&_.ProseMirror_blockquote]:text-muted-foreground [&_.ProseMirror_blockquote]:italic [&_.ProseMirror_pre]:relative [&_.ProseMirror_pre]:my-4 [&_.ProseMirror_pre]:overflow-x-auto [&_.ProseMirror_pre]:rounded-md [&_.ProseMirror_pre]:border [&_.ProseMirror_pre]:border-border [&_.ProseMirror_pre]:bg-muted [&_.ProseMirror_pre]:p-4 [&_.ProseMirror_pre]:font-mono [&_.ProseMirror_pre]:text-sm [&_.ProseMirror_pre_code]:bg-transparent [&_.ProseMirror_pre_code]:p-0 [&_.ProseMirror_pre_code]:font-mono [&_.ProseMirror_pre_code]:text-foreground"
                    />
                </div>
            </div>

            <LinkDialog editor={editor} isOpen={linkDialogOpen} onOpenChange={setLinkDialogOpen} />

            <ImageDialog editor={editor} isOpen={imageDialogOpen} onOpenChange={setImageDialogOpen} />

            <EmojiDialog editor={editor} isOpen={emojiDialogOpen} onOpenChange={setEmojiDialogOpen} />
        </>
    );
}
