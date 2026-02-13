import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

export const useEvents = (year, month) => {
    const queryClient = useQueryClient();

    // FETCH EVENTS
    const fetchEvents = useQuery({
        queryKey: ['events', year, month],
        queryFn: async () => {
            const res = await axios.get(`/api/events?year=${year}&month=${month}`);
            return res.data.data;
        },
        keepPreviousData: true,
    });

    // ADD EVENT
    const addEvent = useMutation({
        mutationFn: async (event) => {
            const res = await axios.post('/api/events', event);
            return res.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['events', year, month] });
        },
    });

    // UPDATE EVENT
    const updateEvent = useMutation({
        mutationFn: async ({ id, event }) => {
            const res = await axios.put(`/api/events/${id}`, event);
            return res.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['events', year, month] });
        },
    });

    // DELETE EVENT
    const deleteEvent = useMutation({
        mutationFn: async (id) => {
            await axios.delete(`/api/events/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['events', year, month] });
        },
    });

    return {
        fetchEvents,
        addEvent,
        updateEvent,
        deleteEvent,
    };
};
