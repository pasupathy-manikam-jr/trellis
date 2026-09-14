import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button, type ButtonProps } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    /** What the button looks like — icon, label, whatever the caller needs. */
    children: React.ReactNode;
    title: string;
    description?: string;
    confirmLabel?: string;
    onConfirm: () => void;
    variant?: ButtonProps['variant'];
    size?: ButtonProps['size'];
    className?: string;
    destructive?: boolean;
};

/**
 * Replaces window.confirm. Themed, focus-trapped, and dismissible with Escape —
 * a native confirm is none of those and cannot be styled.
 */
export function ConfirmButton({
    children,
    title,
    description,
    confirmLabel = 'Confirm',
    onConfirm,
    variant = 'ghost',
    size,
    className,
    destructive = true,
}: Props) {
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <Button type="button" variant={variant} size={size} className={className}>
                    {children}
                </Button>
            </AlertDialogTrigger>

            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && <AlertDialogDescription>{description}</AlertDialogDescription>}
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onConfirm}
                        className={cn(
                            destructive &&
                                'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                        )}
                    >
                        {confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
