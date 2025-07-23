#!/bin/bash

# Git hooks installation script
# This script installs the shared git hooks from .githooks/ directory

set -e

HOOKS_DIR=".githooks"
GIT_HOOKS_DIR=".git/hooks"

echo "🔧 Installing git hooks..."

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo "❌ Error: Not in a git repository root directory"
    exit 1
fi

# Check if .githooks directory exists
if [ ! -d "$HOOKS_DIR" ]; then
    echo "❌ Error: $HOOKS_DIR directory not found"
    exit 1
fi

# Create .git/hooks directory if it doesn't exist
mkdir -p "$GIT_HOOKS_DIR"

# Install each hook from .githooks directory
for hook in "$HOOKS_DIR"/*; do
    if [ -f "$hook" ] && [ "$(basename "$hook")" != "install.sh" ]; then
        hook_name=$(basename "$hook")
        target="$GIT_HOOKS_DIR/$hook_name"
        
        echo "📋 Installing $hook_name hook..."
        cp "$hook" "$target"
        chmod +x "$target"
        echo "✅ $hook_name hook installed successfully"
    fi
done

echo ""
echo "🎉 All git hooks have been installed successfully!"
echo ""
echo "📝 The following hooks are now active:"
for hook in "$GIT_HOOKS_DIR"/*; do
    if [ -f "$hook" ] && [ -x "$hook" ]; then
        echo "  - $(basename "$hook")"
    fi
done
echo ""
echo "💡 These hooks will run automatically on git operations."
echo "💡 To update hooks in the future, run this script again."