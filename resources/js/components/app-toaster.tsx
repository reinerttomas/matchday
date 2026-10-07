import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';

/**
 * Shows the server's flash messages as toasts in the bottom-right corner.
 */
export function AppToaster() {
    useFlashToast();

    return <Toaster position="bottom-right" />;
}
