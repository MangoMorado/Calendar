import esLocale from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Head, router, useForm } from '@inertiajs/react';
import { Calendar as CalendarIcon, Clock, Plus, User } from 'lucide-react';
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

type User = {
    id: number;
    name: string;
};

type Props = {
    events: Event[];
    calendars: Calendar[];
    selectedCalendarId: number | null;
    upcomingAppointments: UpcomingAppointment[];
    users: User[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard({
    events,
    calendars,
    selectedCalendarId,
    upcomingAppointments,
    users,
}: Props) {
    const isMobile = useIsMobile();
    const [selectedCalendar, setSelectedCalendar] = useState<number | null>(
        selectedCalendarId,
    );
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditMode, setIsEditMode] = useState(false);
    const [editingAppointmentId, setEditingAppointmentId] = useState<number | null>(null);
    const [selectedDate, setSelectedDate] = useState<{
        start: string;
        end: string;
    } | null>(null);
    const [allDay, setAllDay] = useState(false);

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
        end.setHours(start.getHours() + 1);

        const startStr = start.toISOString().slice(0, 16);
        const endStr = end.toISOString().slice(0, 16);

        form.setData({
            ...form.data,
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
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setIsEditMode(false);
        setEditingAppointmentId(null);
        setSelectedDate(null);
        setAllDay(false);
        form.reset();
    };

    const handleEventClick = (info: { event: { id: string; title: string; start: Date | null; end: Date | null; extendedProps?: { calendarId?: number; userId?: number } } }) => {
        const appointmentId = parseInt(info.event.id, 10);
        
        // Buscar el evento completo en la lista de eventos
        const event = events.find((e) => e.id === info.event.id);
        
        if (event) {
            const start = info.event.start;
            const end = info.event.end || start;
            
            const startStr = start ? new Date(start).toISOString().slice(0, 16) : '';
            const endStr = end ? new Date(end).toISOString().slice(0, 16) : '';
            
            form.setData({
                title: event.title || '',
                description: event.description || '',
                calendar_id: event.extendedProps?.calendarId?.toString() || '',
                user_id: event.extendedProps?.userId?.toString() || '',
                all_day: event.allDay || false,
                start_time: startStr,
                end_time: endStr,
            });
            
            setAllDay(event.allDay || false);
            setIsEditMode(true);
            setEditingAppointmentId(appointmentId);
            setIsModalOpen(true);
        }
    };

    const handleUpcomingAppointmentClick = (appointmentId: number) => {
        // Buscar el evento en la lista de eventos
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
                all_day: event.allDay || false,
                start_time: startStr,
                end_time: endStr,
            });
            
            setAllDay(event.allDay || false);
            setIsEditMode(true);
            setEditingAppointmentId(appointmentId);
            setIsModalOpen(true);
        } else {
            // Si no se encuentra en events, buscar en upcomingAppointments
            const upcomingAppointment = upcomingAppointments.find(
                (apt) => apt.id === appointmentId,
            );
            
            if (upcomingAppointment) {
                const startDate = new Date(upcomingAppointment.start_time);
                const endDate = new Date(startDate);
                endDate.setHours(startDate.getHours() + 1);
                
                const startStr = startDate.toISOString().slice(0, 16);
                const endStr = endDate.toISOString().slice(0, 16);
                
                // Buscar el calendar_id del nombre del calendario
                const calendar = calendars.find(
                    (cal) => cal.name === upcomingAppointment.calendar_name,
                );
                
                // Buscar el user_id del nombre del usuario
                const user = users.find(
                    (usr) => usr.name === upcomingAppointment.user_name,
                );
                
                form.setData({
                    title: upcomingAppointment.title || '',
                    description: upcomingAppointment.description || '',
                    calendar_id: calendar?.id.toString() || '',
                    user_id: user?.id.toString() || '',
                    all_day: false,
                    start_time: startStr,
                    end_time: endStr,
                });
                
                setAllDay(false);
                setIsEditMode(true);
                setEditingAppointmentId(appointmentId);
                setIsModalOpen(true);
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - Calendario General" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-hidden rounded-xl p-2 sm:p-4">
                {/* Header con título y filtro */}
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

                {/* Grid principal: Calendario (70%) y Próximas Citas (30%) */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_350px]">
                    {/* Calendario Principal */}
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
            </div>

            {/* Modal de Creación/Edición de Cita */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {isEditMode ? 'Editar Cita' : 'Nueva Cita'}
                        </DialogTitle>
                        <DialogDescription>
                            {isEditMode
                                ? 'Modifica la información de la cita'
                                : 'Completa el formulario para crear una nueva cita'}
                        </DialogDescription>
                    </DialogHeader>

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
                                            <option value="">Selecciona un calendario</option>
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
                                            <option value="">Selecciona un usuario</option>
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
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
