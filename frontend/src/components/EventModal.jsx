import { useState, useEffect } from "react";

export default function EventModal({ isOpen, onClose, onSave, event }) {
    const [form, setForm] = useState({
        title: "",
        start: "",
        end: "",
        type: "conge_paye",
    });

    useEffect(() => {
        if (event) setForm({ ...form, ...event });
    }, [event]);

    if (!isOpen) return null;

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form);
    };

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white text-black p-6 rounded-lg w-[400px]">
                <h2 className="text-lg font-bold mb-4">{form.id ? "Modifier" : "Créer"} Event</h2>
                <form onSubmit={handleSubmit} className="space-y-3">
                    <input
                        type="text"
                        name="title"
                        value={form.title}
                        onChange={handleChange}
                        placeholder="Titre"
                        className="w-full border rounded px-3 py-2"
                        required
                    />
                    <input type="date" name="start" value={form.start} onChange={handleChange} className="w-full border rounded px-3 py-2" required />
                    <input type="date" name="end" value={form.end} onChange={handleChange} className="w-full border rounded px-3 py-2" required />
                    <select name="type" value={form.type} onChange={handleChange} className="w-full border rounded px-3 py-2">
                        <option value="conge_paye">Congé payé</option>
                        <option value="rtt">RTT</option>
                        <option value="recup">Récup</option>
                        <option value="maladie">Maladie</option>
                    </select>
                    <div className="flex justify-end space-x-2 mt-4">
                        <button type="button" onClick={onClose} className="px-4 py-2 bg-gray-300 rounded">Annuler</button>
                        <button type="submit" className="px-4 py-2 bg-blue-500 text-white rounded">Sauvegarder</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
