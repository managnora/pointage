import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import { useState } from "react";
import EventModal from "../components/EventModal";
import { useEvents } from "../store/useEvents";

export default function AdminCalendar() {
    const now = new Date();
    const {
        fetchEvents, addEvent, updateEvent, deleteEvent
    } = useEvents(
        now.getFullYear(),
        now.getMonth() + 1
    );

    const [modalOpen, setModalOpen] = useState(false);
    const [selectedEvent, setSelectedEvent] = useState(null);

    const events = fetchEvents.data ?? [];
    const isLoading = fetchEvents.isLoading;

    const handleSave = async (event) => {
        if (event.id) {
            await updateEvent.mutateAsync({ id: event.id, event });
        } else {
            await addEvent.mutateAsync(event);
        }
        setModalOpen(false);
    };

    const handleDelete = async (id) => {
        await deleteEvent.mutateAsync(id);
    };


    const handleEventClick = (info) => {
        const evt = events.find((e) => e.id === Number(info.event.id));
        setSelectedEvent(evt);
        setModalOpen(true);
    };

    const handleDateSelect = (selectInfo) => {
        setSelectedEvent({ start: selectInfo.startStr, end: selectInfo.endStr });
        setModalOpen(true);
    };

    return (
        <div>
            <EventModal
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                onSave={handleSave}
                event={selectedEvent}
            />

            <FullCalendar
                plugins={[dayGridPlugin, interactionPlugin]}
                initialView="dayGridMonth"
                editable
                selectable
                events={events.map((e) => ({
                    id: e.id,
                    title: e.title,
                    start: e.start,
                    end: e.end,
                    backgroundColor: e.color,
                }))}
                eventClick={handleEventClick}
                select={handleDateSelect}
                headerToolbar={{
                    left: "prev,next today",
                    center: "title",
                    right: "dayGridMonth,dayGridWeek",
                }}
            />
        </div>
    );
}
