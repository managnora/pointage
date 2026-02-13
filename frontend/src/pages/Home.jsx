export default function Home() {
    return (
        <div className="text-white">
            <h1 className="text-3xl font-bold mb-4">Bienvenue sur le portail RH</h1>
            <p className="mb-2">Vous pouvez consulter vos horaires, congés et récupérer vos jours de récupération ici.</p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div className="p-4 bg-white/10 rounded-lg shadow">
                    <h2 className="font-semibold text-lg mb-2">Vos prochains congés</h2>
                    <ul className="list-disc list-inside">
                        <li>Pas de congé prévu pour le moment</li>
                    </ul>
                </div>

                <div className="p-4 bg-white/10 rounded-lg shadow">
                    <h2 className="font-semibold text-lg mb-2">Résumé des heures travaillées</h2>
                    <p>0 heures ce mois-ci</p>
                </div>
            </div>
        </div>
    );
}
