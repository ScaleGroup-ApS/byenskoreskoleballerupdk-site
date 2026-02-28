"use client"

import { format } from "date-fns"
import { ChevronDownIcon } from "lucide-react"
import * as React from "react"
import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"

export function DatePickerTime({ 
    value,
    onChange,
    label,
 }: { value: Date | undefined; onChange: (date: Date) => void; label: string }) {
    const [open, setOpen] = React.useState(false)
    const [date, setDate] = React.useState<Date | undefined>(value)

    return (
        <FieldGroup className="mx-auto max-w-xs flex-row">
            <Field>
                <FieldLabel htmlFor="date-picker-optional">{label}</FieldLabel>
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            variant="outline"
                            id="date-picker-optional"
                            className="w-32 justify-between font-normal"
                        >
                            {date ? format(date, "PPP") : `Vælg ${label}`}
                            <ChevronDownIcon />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto overflow-hidden p-0" align="start">
                        <Calendar
                            mode="single"
                            selected={date}
                            captionLayout="dropdown"
                            defaultMonth={date}
                            onSelect={(date) => {
                                setDate(date)
                                setOpen(false)

                                if (date) { onChange(date); }
                            }}
                        />
                    </PopoverContent>
                </Popover>
            </Field>
            <Field className="w-32">
                <FieldLabel htmlFor="time-picker-optional">Tid</FieldLabel>
                <Input
                    type="time"
                    id="time-picker-optional"
                    step="1"
                    defaultValue="10:30:00"
                    className="bg-background appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                />
            </Field>
        </FieldGroup>
    )
}
