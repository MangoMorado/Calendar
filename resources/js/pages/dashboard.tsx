import esLocale from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Calendar as CalendarIcon, CalendarOff, Clock, Plus, User } from 'lucide-react';
import { useEffect, useState } from 'react';

import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { store, update } from '@/routes/appointments';
import { create as createCalendar } from '@/routes/calendars';
import type { BreadcrumbItem } from '@/types';

/**
 * Hook para detectar si es un dispositivo móvil
 */
function useIsMobile(): boolean {
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = (): void => {
            setIsMobile(window.innerWidth < 768);
        };

        checkMobile();
        window.addEventListener('resize', checkMobile);

        return () => {
            window.removeEventListener('resize', checkMobile);
        };
    }, []);

    return isMobile;
}

type Calendar = {
    id: number;
    name: string;
    color: string;
    user: {
        id: number;
        name: string;
    } | null;
};

type Event = {
    id: string;
    title: string;
    start: string;
    end: string;
    description: string | null;
    allDay: boolean;
    backgroundColor: string;
    borderColor: string;
    extendedProps?: {
        calendarId: number;
        calendarName: string;
        userId: number | null;
        userName: string;
    };
};

type UpcomingAppointment = {
    id: number;
    title: string;
    start_time: string;
    start_time_formatted: string;
    description: string | null;
    color: string;
    calendar_name: string;
    user_name: string;
};

type ViewingAppointment = {
    title: string;
    description: string | null;
    calendarName: string;
    userName: string;
    startFormatted: string;
    endFormatted: string;
    allDay: boolean;
};

type User = {
    id: number;
    name: string;
};

type CalendarConfig = {
    start_time: string;
    end_time: string;
    slot_duration: number;
    business_days: number[];
    time_format: string;
    timezone: string;
};

type Props = {
    events: Event[];
    calendars: Calendar[];
    selectedCalendarId: number | null;
    upcomingAppointments: UpcomingAppointment[];
    users: User[];
    calendarConfig?: CalendarConfig;
    canCreateCalendar?: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const defaultCalendarConfig: CalendarConfig = {
    start_time: '06:00',
    end_time: '19:00',
    slot_duration: 30,
    business_days: [1, 2, 3, 4, 5, 6],
    time_format: '12',
    timezone: 'America/Bogota',
};

function formatDateTime(iso: string, use24h: boolean): string {
    const d = new Date(iso);
    if (use24h) {
        return d.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }
    return d.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

export default function Dashboard({
    events,
    calendars,
    selectedCalendarId,
    upcomingAppointments,
    users,
    calendarConfig = defaultCalendarConfig,
    canCreateCalendar = false,
}: Props) {
    const isMobile = useIsMobile();
    const [selectedCalendar, setSelectedCalendar] = useState<number | null>(
        selectedCalendarId,
    );
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditMode, setIsEditMode] = useState(false);
    const [isViewMode, setIsViewMode] = useState(false);
    const [viewingAppointment, setViewingAppointment] =
        useState<ViewingAppointment | null>(null);
    const [editingAppointmentId, setEditingAppointmentId] = useState<number | null>(null);
    const [selectedDate, setSelectedDate] = useState<{
        start: string;
        end: string;
    } | null>(null);
    const [allDay, setAllDay] = useState(false);

    const use24h = calendarConfig?.time_format === '24';

    const form = useForm({
        title: '',
        description: '',
        calendar_id: '',
        user_id: '',
        all_day: false,
        start_time: '',
        end_time: '',
    });

    const handleCalendarFilter = (calendarId: string) => {
        const id = calendarId === 'all' ? null : parseInt(calendarId, 10);
        setSelectedCalendar(id);
        router.get(
            dashboard().url,
            { calendar_id: id },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const filteredEvents = selectedCalendar
        ? events.filter(
              (event) =>
                  event.extendedProps?.calendarId === selectedCalendar,
          )
        : events;

    const handleDateClick = (info: { date: Date; allDay: boolean }) => {
        const start = new Date(info.date);
        const end = new Date(start);
        const slotMinutes = calendarConfig?.slot_duration ?? 30;
        end.setTime(start.getTime() + slotMinutes * 60 * 1000);

        const startStr = start.toISOString().slice(0, 16);
        const endStr = end.toISOString().slice(0, 16);

        form.setData({
            ...form.data,
            calendar_id: calendars.length > 0 ? String(calendars[0].id) : '',
            user_id: users.length > 0 ? String(users[0].id) : '',
            start_time: startStr,
            end_time: endStr,
        });

        setSelectedDate({
            start: startStr,
            end: endStr,
        });
        setIsModalOpen(true);
    };

    const handleNewAppointment = () => {
        setSelectedDate(null);
        form.setData({
            ...form.data,
            title: '',
            description: '',
            calendar_id: calendars.length > 0 ? String(calendars[0].id) : '',
            user_id: users.length > 0 ? String(users[0].id) : '',
            all_day: false,
            start_time: '',
            end_time: '',
        });
        setAllDay(false);
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setIsEditMode(false);
        setIsViewMode(false);
        setViewingAppointment(null);
        setEditingAppointmentId(null);
        setSelectedDate(null);
        setAllDay(false);
        form.reset();
    };

    const handleEventClick = (info: { event: { id: string; title: string; start: Date | null; end: Date | null; extendedProps?: { calendarId?: number; calendarName?: string; userId?: number; userName?: string } } }) => {
        const appointmentId = parseInt(info.event.id, 10);
        const event = events.find((e) => e.id === info.event.id);
        if (!event) return;

        const start = info.event.start;
        const end = info.event.end || start;
        const startStr = start ? new Date(start).toISOString().slice(0, 16) : '';
        const endStr = end ? new Date(end).toISOString().slice(0, 16) : '';

        form.setData({
            title: event.title || '',
            description: event.description || '',
            calendar_id: event.extendedProps?.calendarId?.toString() || '',
            user_id: event.extendedProps?.userId?.toString() || '',
            all_day: event.allDay ?? false,
            start_time: startStr,
            end_time: endStr,
        });

        setAllDay(event.allDay ?? false);
        setViewingAppointment({
            title: event.title || '',
            description: event.description ?? null,
            calendarName: event.extendedProps?.calendarName ?? '—',
            userName: event.extendedProps?.userName ?? 'Sin asignar',
            startFormatted: event.start ? formatDateTime(event.start, use24h) : '—',
            endFormatted: event.end ? formatDateTime(event.end, use24h) : '—',
            allDay: event.allDay ?? false,
        });
        setIsViewMode(true);
        setIsEditMode(true);
        setEditingAppointmentId(appointmentId);
        setIsModalOpen(true);
    };

    const handleUpcomingAppointmentClick = (appointmentId: number) => {
        const event = events.find((e) => e.id === appointmentId.toString());

        if (event) {
            const startDate = new Date(event.start);
            const endDate = new Date(event.end);
            const startStr = startDate.toISOString().slice(0, 16);
            const endStr = endDate.toISOString().slice(0, 16);

            form.setData({
                title: event.title || '',
                description: event.description || '',
                calendar_id: event.extendedProps?.calendarId?.toString() || '',
                user_id: event.extendedProps?.userId?.toString() || '',
                all_day: event.allDay ?? false,
                start_time: startStr,
                end_time: endStr,
            });
            setAllDay(event.allDay ?? false);
            setViewingAppointment({
                title: event.title || '',
                description: event.description ?? null,
                calendarName: event.extendedProps?.calendarName ?? '—',
                userName: event.extendedProps?.userName ?? 'Sin asignar',
                startFormatted: formatDateTime(event.start, use24h),
                endFormatted: formatDateTime(event.end, use24h),
                allDay: event.allDay ?? false,
            });
        } else {
            const apt = upcomingAppointments.find((a) => a.id === appointmentId);
            if (!apt) return;

            const startDate = new Date(apt.start_time);
            const endDate = new Date(startDate);
            endDate.setHours(startDate.getHours() + 1);
            const startStr = startDate.toISOString().slice(0, 16);
            const endStr = endDate.toISOString().slice(0, 16);

            const calendar = calendars.find((c) => c.name === apt.calendar_name);
            const user = users.find((u) => u.name === apt.user_name);

            form.setData({
                title: apt.title || '',
                description: apt.description || '',
                calendar_id: calendar?.id.toString() || '',
                user_id: user?.id.toString() || '',
                all_day: false,
                start_time: startStr,
                end_time: endStr,
            });
            setAllDay(false);
            setViewingAppointment({
                title: apt.title || '',
                description: apt.description ?? null,
                calendarName: apt.calendar_name,
                userName: apt.user_name,
                startFormatted: apt.start_time_formatted,
                endFormatted: endDate.toLocaleString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: !use24h,
                }),
                allDay: false,
            });
        }

        setIsViewMode(true);
        setIsEditMode(true);
        setEditingAppointmentId(appointmentId);
        setIsModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - Calendario General" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-hidden rounded-xl p-2 sm:p-4">
                {calendars.length === 0 ? (
                    <div className="flex min-h-[400px] flex-1 flex-col items-center justify-center gap-4 rounded-lg border bg-card p-8 sm:min-h-[500px]">
                        <CalendarOff className="h-16 w-16 text-muted-foreground/50 sm:h-20 sm:w-20" />
                        <p className="max-w-md text-center text-muted-foreground text-sm sm:text-base">
                            No hay calendarios disponibles. Crea un
                            calendario primero para empezar a agendar
                            citas.
                        </p>
                        {canCreateCalendar && (
                            <Button asChild>
                                <Link href={createCalendar().url}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear calendario
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-xl font-semibold sm:text-2xl">
                                    Calendario General
                                </h1>
                                <p className="text-muted-foreground text-xs sm:text-sm">
                                    Todos los eventos de calendarios activos
                                </p>
                            </div>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                <div className="flex w-full items-center gap-2 sm:w-auto">
                                    <label
                                        htmlFor="calendar-filter"
                                        className="text-xs font-medium sm:text-sm"
                                    >
                                        Filtrar:
                                    </label>
                                    <select
                                        id="calendar-filter"
                                        value={selectedCalendar?.toString() || 'all'}
                                        onChange={(e) =>
                                            handleCalendarFilter(e.target.value)
                                        }
                                        className="flex h-9 flex-1 items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-xs shadow-xs ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-[200px] sm:text-sm"
                                    >
                                        <option value="all">Todos</option>
                                        {calendars.map((calendar) => (
                                            <option
                                                key={calendar.id}
                                                value={calendar.id.toString()}
                                            >
                                                {calendar.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <Button
                                    className="w-full sm:w-auto"
                                    size={isMobile ? 'sm' : 'default'}
                                    onClick={handleNewAppointment}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva Cita
                                </Button>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_350px]">
                            <div className="min-h-[400px] rounded-lg border bg-card p-2 sm:min-h-[600px] sm:p-4">
                                <FullCalendar
                            plugins={[
                                dayGridPlugin,
                                timeGridPlugin,
                                interactionPlugin,
                            ]}
                            initialView="timeGridWeek"
                            headerToolbar={{
                                left: isMobile ? 'prev,next' : 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay',
                            }}
                            locale={esLocale}
                            events={filteredEvents}
                            editable={!isMobile}
                            droppable={!isMobile}
                            selectable={!isMobile}
                            height="auto"
                            allDaySlot={!isMobile}
                            navLinks={!isMobile}
                            dayMaxEvents={isMobile ? 2 : true}
                            expandRows={isMobile}
                            firstDay={1}
                            nowIndicator={true}
                            eventTimeFormat={
                                calendarConfig?.time_format === '24'
                                    ? { hour: '2-digit', minute: '2-digit', hour12: false }
                                    : { hour: '2-digit', minute: '2-digit', hour12: true }
                            }
                            slotLabelFormat={
                                calendarConfig?.time_format === '24'
                                    ? { hour: '2-digit', minute: '2-digit', hour12: false }
                                    : { hour: '2-digit', minute: '2-digit', hour12: true }
                            }
                            slotMinTime={
                                calendarConfig?.start_time != null
                                    ? `${String(calendarConfig.start_time).slice(0, 5)}:00`
                                    : '06:00:00'
                            }
                            slotMaxTime={
                                calendarConfig?.end_time != null
                                    ? `${String(calendarConfig.end_time).slice(0, 5)}:00`
                                    : '19:00:00'
                            }
                            slotDuration={
                                calendarConfig?.slot_duration != null
                                    ? { minutes: calendarConfig.slot_duration }
                                    : { minutes: 30 }
                            }
                            hiddenDays={(() => {
                                const days = calendarConfig?.business_days ?? [1, 2, 3, 4, 5, 6];
                                return [0, 1, 2, 3, 4, 5, 6].filter(
                                    (fcDay) =>
                                        !days.includes(fcDay === 0 ? 7 : fcDay),
                                );
                            })()}
                            dateClick={handleDateClick}
                            eventClick={handleEventClick}
                            eventDrop={(info) => {
                                const appointmentId = parseInt(info.event.id, 10);
                                const start = info.event.start;
                                const end = info.event.end || start;

                                router.patch(
                                    update({ appointment: appointmentId }).url,
                                    {
                                        start_time: start?.toISOString(),
                                        end_time: end?.toISOString(),
                                    },
                                    {
                                        preserveState: true,
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            // Recargar la página para actualizar los eventos
                                            router.reload({ only: ['events'] });
                                        },
                                    },
                                );
                            }}
                            eventResize={(info) => {
                                const appointmentId = parseInt(info.event.id, 10);
                                const start = info.event.start;
                                const end = info.event.end || start;

                                router.patch(
                                    update({ appointment: appointmentId }).url,
                                    {
                                        start_time: start?.toISOString(),
                                        end_time: end?.toISOString(),
                                    },
                                    {
                                        preserveState: true,
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            // Recargar la página para actualizar los eventos
                                            router.reload({ only: ['events'] });
                                        },
                                    },
                                );
                            }}
                            moreLinkClick="popover"
                        />
                    </div>

                    {/* Panel de Próximas Citas */}
                    <div className="flex flex-col rounded-lg border bg-card">
                        <div className="border-b bg-primary p-3 sm:p-4">
                            <h3 className="flex items-center gap-2 text-base font-semibold text-primary-foreground sm:text-lg">
                                <Clock className="h-4 w-4 sm:h-5 sm:w-5" />
                                Próximas Citas
                            </h3>
                        </div>
                        <div className="max-h-[400px] flex-1 overflow-y-auto p-2 sm:max-h-none sm:p-4">
                            {upcomingAppointments.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-6 text-center sm:py-8">
                                    <CalendarIcon className="mb-2 h-8 w-8 opacity-20 sm:h-12 sm:w-12" />
                                    <p className="text-muted-foreground text-xs sm:text-sm">
                                        No hay citas próximas
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-2 sm:space-y-3">
                                    {upcomingAppointments.map(
                                        (appointment) => (
                                            <div
                                                key={appointment.id}
                                                onClick={() => handleUpcomingAppointmentClick(appointment.id)}
                                                className="group relative cursor-pointer rounded-lg border bg-background p-2 text-xs transition-all hover:border-primary hover:shadow-md sm:p-3 sm:text-sm"
                                                style={{
                                                    borderLeftColor:
                                                        appointment.color,
                                                    borderLeftWidth: '4px',
                                                }}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex-1 space-y-1">
                                                        <h4 className="font-medium text-xs sm:text-sm">
                                                            {appointment.title}
                                                        </h4>
                                                        <div className="flex flex-wrap items-center gap-1.5 text-[10px] text-muted-foreground sm:gap-2 sm:text-xs">
                                                            <span className="flex items-center gap-1">
                                                                <Clock className="h-2.5 w-2.5 sm:h-3 sm:w-3" />
                                                                {
                                                                    appointment.start_time_formatted
                                                                }
                                                            </span>
                                                            <Badge
                                                                variant="outline"
                                                                className="text-[10px] sm:text-xs"
                                                            >
                                                                {
                                                                    appointment.calendar_name
                                                                }
                                                            </Badge>
                                                        </div>
                                                        {appointment.user_name && (
                                                            <div className="flex items-center gap-1 text-[10px] text-muted-foreground sm:text-xs">
                                                                <User className="h-2.5 w-2.5 sm:h-3 sm:w-3" />
                                                                {
                                                                    appointment.user_name
                                                                }
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                    </>
                )}
            </div>

            {/* Modal de Creación/Edición de Cita */}
            <Dialog open={isModalOpen} onOpenChange={(open) => (open ? setIsModalOpen(true) : handleCloseModal())}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {isViewMode
                                ? 'Ver Cita'
                                : isEditMode
                                    ? 'Editar Cita'
                                    : 'Nueva Cita'}
                        </DialogTitle>
                        <DialogDescription>
                            {isViewMode
                                ? 'Información de la cita'
                                : isEditMode
                                    ? 'Modifica la información de la cita'
                                    : 'Completa el formulario para crear una nueva cita'}
                        </DialogDescription>
                    </DialogHeader>

                    {isViewMode && viewingAppointment ? (
                        <div className="space-y-4">
                            <div className="space-y-3 rounded-lg border p-4">
                                <div>
                                    <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Título</p>
                                    <p className="mt-1 text-sm font-medium">{viewingAppointment.title}</p>
                                </div>
                                {viewingAppointment.description && (
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Descripción</p>
                                        <p className="mt-1 whitespace-pre-wrap text-sm">{viewingAppointment.description}</p>
                                    </div>
                                )}
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Calendario</p>
                                        <p className="mt-1 text-sm">{viewingAppointment.calendarName}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Usuario asignado</p>
                                        <p className="mt-1 text-sm">{viewingAppointment.userName}</p>
                                    </div>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Inicio</p>
                                        <p className="mt-1 text-sm">{viewingAppointment.startFormatted}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Fin</p>
                                        <p className="mt-1 text-sm">{viewingAppointment.endFormatted}</p>
                                    </div>
                                </div>
                                {viewingAppointment.allDay && (
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Todo el día</p>
                                        <p className="mt-1 text-sm">Sí</p>
                                    </div>
                                )}
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={handleCloseModal}>
                                    Cerrar
                                </Button>
                                <Button onClick={() => setIsViewMode(false)}>
                                    Editar
                                </Button>
                            </DialogFooter>
                        </div>
                    ) : (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (isEditMode && editingAppointmentId) {
                                form.patch(update({ appointment: editingAppointmentId }).url, {
                                    onSuccess: () => {
                                        handleCloseModal();
                                        router.reload({ only: ['events', 'upcomingAppointments'] });
                                    },
                                });
                            } else {
                                form.post(store().url, {
                                    onSuccess: () => {
                                        handleCloseModal();
                                        router.reload({ only: ['events', 'upcomingAppointments'] });
                                    },
                                });
                            }
                        }}
                    >
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">
                                        Título <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        type="text"
                                        required
                                        value={form.data.title}
                                        onChange={(e) =>
                                            form.setData('title', e.target.value)
                                        }
                                        className="w-full"
                                    />
                                    <InputError message={form.errors.title} className="mt-2" />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Descripción</Label>
                                    <textarea
                                        id="description"
                                        rows={3}
                                        value={form.data.description}
                                        onChange={(e) =>
                                            form.setData('description', e.target.value)
                                        }
                                        className="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError message={form.errors.description} className="mt-2" />
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="calendar_id">
                                            Calendario <span className="text-destructive">*</span>
                                        </Label>
                                        <select
                                            id="calendar_id"
                                            required
                                            value={form.data.calendar_id}
                                            onChange={(e) =>
                                                form.setData('calendar_id', e.target.value)
                                            }
                                            className="flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {calendars.length === 0 && (
                                                <option value="">Selecciona un calendario</option>
                                            )}
                                            {calendars.map((calendar) => (
                                                <option key={calendar.id} value={calendar.id}>
                                                    {calendar.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={form.errors.calendar_id} className="mt-2" />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="user_id">
                                            Usuario Asignado <span className="text-destructive">*</span>
                                        </Label>
                                        <select
                                            id="user_id"
                                            required
                                            value={form.data.user_id}
                                            onChange={(e) =>
                                                form.setData('user_id', e.target.value)
                                            }
                                            className="flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {users.length === 0 && (
                                                <option value="">Selecciona un usuario</option>
                                            )}
                                            {users.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={form.errors.user_id} className="mt-2" />
                                    </div>
                                </div>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="all_day">Todo el día</Label>
                                        <p className="text-muted-foreground text-sm">
                                            La cita ocupará todo el día seleccionado
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            id="all_day"
                                            checked={allDay}
                                            onCheckedChange={(checked) => {
                                                setAllDay(checked);
                                                form.setData('all_day', checked);
                                            }}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="start_time">
                                            Hora de inicio <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="start_time"
                                            type="datetime-local"
                                            required
                                            value={form.data.start_time || selectedDate?.start || ''}
                                            onChange={(e) =>
                                                form.setData('start_time', e.target.value)
                                            }
                                            className="w-full"
                                        />
                                        <InputError message={form.errors.start_time} className="mt-2" />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="end_time">
                                            Hora de fin <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="end_time"
                                            type="datetime-local"
                                            required
                                            value={form.data.end_time || selectedDate?.end || ''}
                                            onChange={(e) =>
                                                form.setData('end_time', e.target.value)
                                            }
                                            className="w-full"
                                        />
                                        <InputError message={form.errors.end_time} className="mt-2" />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleCloseModal}
                                        disabled={form.processing}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button type="submit" disabled={form.processing}>
                                        {form.processing
                                            ? isEditMode
                                                ? 'Guardando...'
                                                : 'Creando...'
                                            : isEditMode
                                                ? 'Guardar Cambios'
                                                : 'Crear Cita'}
                                    </Button>
                                </DialogFooter>
                            </div>
                    </form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
