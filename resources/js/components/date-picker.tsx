import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { format, parseISO } from 'date-fns';
import { CalendarIcon, X } from 'lucide-react';
import { useState } from 'react';

type Props = {
    /** ISO date string (yyyy-MM-dd) or '' for unset. */
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    id?: string;
    /** Days before this are not selectable. Defaults to today. */
    fromDate?: Date;
};

export function DatePicker({
    value,
    onChange,
    placeholder = 'Pick a date',
    id,
    fromDate = new Date(),
}: Props) {
    const [open, setOpen] = useState(false);
    const selected = value ? parseISO(value) : undefined;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    className={cn('h-10 w-full justify-start font-normal', !value && 'text-muted-foreground')}
                >
                    <CalendarIcon className="size-4 shrink-0" />
                    <span className="flex-1 text-left">
                        {selected ? format(selected, 'd MMM yyyy') : placeholder}
                    </span>
                    {value && (
                        <span
                            role="button"
                            aria-label="Clear date"
                            className="hover:text-foreground text-muted-foreground"
                            onClick={(e) => {
                                e.stopPropagation();
                                onChange('');
                            }}
                        >
                            <X className="size-3.5" />
                        </span>
                    )}
                </Button>
            </PopoverTrigger>

            <PopoverContent className="p-0" align="start">
                <Calendar
                    mode="single"
                    autoFocus
                    selected={selected}
                    defaultMonth={selected}
                    disabled={{ before: fromDate }}
                    onSelect={(date) => {
                        onChange(date ? format(date, 'yyyy-MM-dd') : '');
                        setOpen(false);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}
