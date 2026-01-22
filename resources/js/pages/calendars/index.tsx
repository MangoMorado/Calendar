import { Head, Link, router } from '@inertiajs/react';
import { Calendar as CalendarIcon, Edit, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { create, destroy, index } from '@/routes/calendars';
import type { BreadcrumbItem, PaginatedData } from '@/types';

type Calendar = {
    id: number;
    name: string;
    description: string | null;
    color: string;
    is_active: boolean;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    created_at: string;
    updated_at: string;
};

type Props = {
    calendars: PaginatedData<Calendar>;
    canCreate: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Calendarios',
        href: index().url,
    },
];

export default function CalendarsIndex({ calendars, canCreate }: Props) {
    const handleDelete = (id: number) => {
        router.delete(destroy(id).url, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Calendarios" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Calendarios</h1>
                        <p className="text-muted-foreground text-sm">
                            Gestiona los calendarios del sistema
                        </p>
                    </div>
                    {canCreate && (
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Calendario
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="rounded-lg border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Usuario
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Color
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {calendars.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-8 text-center text-muted-foreground"
                                        >
                                            <CalendarIcon className="mx-auto mb-2 h-12 w-12 opacity-20" />
                                            <p>No hay calendarios registrados</p>
                                        </td>
                                    </tr>
                                ) : (
                                    calendars.data.map((calendar: Calendar) => (
                                        <tr
                                            key={calendar.id}
                                            className="hover:bg-muted/50 transition-colors"
                                        >
                                            <td className="px-6 py-4">
                                                <div className="font-medium">
                                                    {calendar.name}
                                                </div>
                                                {calendar.description && (
                                                    <div className="text-muted-foreground text-sm">
                                                        {calendar.description.length > 50
                                                            ? `${calendar.description.substring(0, 50)}...`
                                                            : calendar.description}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {calendar.user ? (
                                                    <div>
                                                        <div className="font-medium">
                                                            {calendar.user.name}
                                                        </div>
                                                        <div className="text-muted-foreground text-xs">
                                                            {calendar.user.email}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Sistema
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    variant={
                                                        calendar.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {calendar.is_active
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <div
                                                        className="h-6 w-6 rounded border"
                                                        style={{
                                                            backgroundColor:
                                                                calendar.color,
                                                        }}
                                                    />
                                                    <span className="text-muted-foreground text-xs">
                                                        {calendar.color}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {canCreate && (
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={`/calendars/${calendar.id}/edit`}
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Dialog>
                                                                <DialogTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                    >
                                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                                    </Button>
                                                                </DialogTrigger>
                                                                <DialogContent>
                                                                    <DialogHeader>
                                                                        <DialogTitle>
                                                                            ¿Eliminar
                                                                            calendario?
                                                                        </DialogTitle>
                                                                        <DialogDescription>
                                                                            Esta
                                                                            acción
                                                                            no
                                                                            se
                                                                            puede
                                                                            deshacer.
                                                                            Se
                                                                            eliminará
                                                                            el
                                                                            calendario
                                                                            "{calendar.name}".
                                                                        </DialogDescription>
                                                                    </DialogHeader>
                                                                    <DialogFooter>
                                                                        <DialogClose asChild>
                                                                            <Button
                                                                                variant="outline"
                                                                            >
                                                                                Cancelar
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button
                                                                            variant="destructive"
                                                                            onClick={() =>
                                                                                handleDelete(
                                                                                    calendar.id,
                                                                                )
                                                                            }
                                                                        >
                                                                            Eliminar
                                                                        </Button>
                                                                    </DialogFooter>
                                                                </DialogContent>
                                                            </Dialog>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {calendars.links && calendars.links.length > 3 && (
                        <div className="border-t px-6 py-4">
                            <div className="flex items-center justify-between">
                                <div className="text-muted-foreground text-sm">
                                    Mostrando {calendars.from} a {calendars.to} de{' '}
                                    {calendars.total} resultados
                                </div>
                                <div className="flex gap-2">
                                    {calendars.links.map((link: { url: string | null; label: string; active: boolean }, index: number) => (
                                        <Button
                                            key={index}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            asChild
                                            disabled={!link.url}
                                        >
                                            <Link
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
