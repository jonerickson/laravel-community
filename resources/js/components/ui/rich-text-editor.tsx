import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import Emoji, { gitHubEmojis } from '@tiptap/extension-emoji';
import Image from '@tiptap/extension-image';
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
    MoreHorizontal,
    Quote,
    Redo,
    Smile,
    Strikethrough,
    Underline,
    Undo,
} from 'lucide-react';
import { useEffect, useState } from 'react';

const ResizableImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: (element) => element.getAttribute('width'),
                renderHTML: (attributes) => {
                    if (!attributes.width) {
                        return {};
                    }
                    return { width: attributes.width };
                },
            },
            height: {
                default: null,
                parseHTML: (element) => element.getAttribute('height'),
                renderHTML: (attributes) => {
                    if (!attributes.height) {
                        return {};
                    }
                    return { height: attributes.height };
                },
            },
        };
    },
    addNodeView() {
        return ({ node, HTMLAttributes, getPos, editor }) => {
            const container = document.createElement('div');
            container.className = 'relative inline-block group max-w-full';

            const img = document.createElement('img');
            Object.entries(HTMLAttributes).forEach(([key, value]) => {
                img.setAttribute(key, value);
            });
            img.className = 'max-w-full h-auto rounded-md cursor-pointer';
            img.style.width = node.attrs.width ? `${node.attrs.width}px` : 'auto';
            img.style.height = node.attrs.height ? `${node.attrs.height}px` : 'auto';

            const resizeHandle = document.createElement('div');
            resizeHandle.className =
                'absolute bottom-0 right-0 w-3 h-3 bg-background border border-border rounded-sm cursor-se-resize opacity-0 group-hover:opacity-100 transition-opacity shadow-sm';
            resizeHandle.style.transform = 'translate(50%, 50%)';

            const gripDots = document.createElement('div');
            gripDots.className = 'absolute inset-0 flex items-center justify-center pointer-events-none';
            gripDots.innerHTML = `
                <svg width="8" height="8" viewBox="0 0 8 8" class="text-muted-foreground">
                    <circle cx="2" cy="6" r="0.5" fill="currentColor"/>
                    <circle cx="6" cy="2" r="0.5" fill="currentColor"/>
                    <circle cx="6" cy="6" r="0.5" fill="currentColor"/>
                </svg>
            `;
            resizeHandle.appendChild(gripDots);

            let isResizing = false;
            let startX = 0;
            let startWidth = 0;
            let startHeight = 0;

            const startResize = (e: MouseEvent) => {
                e.preventDefault();
                isResizing = true;
                startX = e.clientX;
                startWidth = img.offsetWidth;
                startHeight = img.offsetHeight;
                document.addEventListener('mousemove', doResize);
                document.addEventListener('mouseup', stopResize);
            };

            const doResize = (e: MouseEvent) => {
                if (!isResizing) return;

                const deltaX = e.clientX - startX;

                const newWidth = Math.max(50, startWidth + deltaX);
                const aspectRatio = startWidth / startHeight;
                const newHeight = newWidth / aspectRatio;

                img.style.width = `${newWidth}px`;
                img.style.height = `${newHeight}px`;
            };

            const stopResize = () => {
                if (!isResizing) return;
                isResizing = false;

                const width = parseInt(img.style.width);
                const height = parseInt(img.style.height);

                const pos = getPos();
                if (typeof pos === 'number') {
                    editor.view.dispatch(
                        editor.view.state.tr.setNodeMarkup(pos, null, {
                            ...node.attrs,
                            width,
                            height,
                        }),
                    );
                }

                document.removeEventListener('mousemove', doResize);
                document.removeEventListener('mouseup', stopResize);
            };

            resizeHandle.addEventListener('mousedown', startResize);

            container.appendChild(img);
            container.appendChild(resizeHandle);

            return {
                dom: container,
                update: (updatedNode) => {
                    if (updatedNode.type !== node.type) return false;

                    if (updatedNode.attrs.width) {
                        img.style.width = `${updatedNode.attrs.width}px`;
                    }
                    if (updatedNode.attrs.height) {
                        img.style.height = `${updatedNode.attrs.height}px`;
                    }

                    return true;
                },
            };
        };
    },
});

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

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editor) return;

        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                const dataUrl = event.target?.result as string;
                editor
                    .chain()
                    .focus()
                    .setImage({
                        src: dataUrl,
                        alt: altText || file.name,
                    })
                    .run();
            };
            reader.readAsDataURL(file);
        } else if (imageUrl) {
            editor
                .chain()
                .focus()
                .setImage({
                    src: imageUrl,
                    alt: altText || 'Image',
                })
                .run();
        }

        onOpenChange(false);
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogContent onOpenAutoFocus={(e) => e.preventDefault()}>
                <DialogHeader>
                    <DialogTitle>Insert image</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 pb-4">
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Upload Image</label>
                        <Input type="file" accept="image/*" onChange={handleFileChange} />
                    </div>
                    <div className="text-center text-sm text-muted-foreground">or</div>
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Image URL</label>
                        <Input
                            value={imageUrl}
                            onChange={(e) => setImageUrl(e.target.value)}
                            placeholder="https://example.com/image.jpg"
                            disabled={!!file}
                        />
                    </div>
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Alt Text (Optional)</label>
                        <Input value={altText} onChange={(e) => setAltText(e.target.value)} placeholder="Describe the image" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={handleSubmit} disabled={!file && !imageUrl}>
                        Insert Image
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
            ResizableImage,
            Emoji.configure({
                emojis: gitHubEmojis,
                enableEmoticons: true,
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
                        className="prose prose-sm max-w-none focus-within:outline-none [&_.ProseMirror]:min-h-[120px] [&_.ProseMirror]:cursor-text [&_.ProseMirror]:border-none [&_.ProseMirror]:outline-none [&_.ProseMirror_a]:cursor-pointer [&_.ProseMirror_a]:text-blue-600 [&_.ProseMirror_a]:underline [&_.ProseMirror_a]:decoration-blue-600 [&_.ProseMirror_a]:underline-offset-2 dark:[&_.ProseMirror_a]:text-blue-400 dark:[&_.ProseMirror_a]:decoration-blue-400 [&_.ProseMirror_blockquote]:my-4 [&_.ProseMirror_blockquote]:border-l-4 [&_.ProseMirror_blockquote]:border-border [&_.ProseMirror_blockquote]:bg-muted [&_.ProseMirror_blockquote]:py-4 [&_.ProseMirror_blockquote]:pl-4 [&_.ProseMirror_blockquote]:text-muted-foreground [&_.ProseMirror_blockquote]:italic [&_.ProseMirror_pre]:relative [&_.ProseMirror_pre]:my-4 [&_.ProseMirror_pre]:overflow-x-auto [&_.ProseMirror_pre]:rounded-md [&_.ProseMirror_pre]:border [&_.ProseMirror_pre]:border-border [&_.ProseMirror_pre]:bg-muted [&_.ProseMirror_pre]:p-4 [&_.ProseMirror_pre]:font-mono [&_.ProseMirror_pre]:text-sm [&_.ProseMirror_pre_code]:bg-transparent [&_.ProseMirror_pre_code]:p-0 [&_.ProseMirror_pre_code]:font-mono [&_.ProseMirror_pre_code]:text-foreground"
                    />
                </div>
            </div>

            <LinkDialog editor={editor} isOpen={linkDialogOpen} onOpenChange={setLinkDialogOpen} />

            <ImageDialog editor={editor} isOpen={imageDialogOpen} onOpenChange={setImageDialogOpen} />

            <EmojiDialog editor={editor} isOpen={emojiDialogOpen} onOpenChange={setEmojiDialogOpen} />
        </>
    );
}
