import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = "http://tpmongo-php"; // via Docker network

export const options = {
    vus: 20,             // 20 utilisateurs en parallèle
    duration: '30s',     // pendant 30 secondes
};

export default function () {
    // -----------------------------
    // 1) AFFICHAGE LISTE / PAGINATION
    // -----------------------------
    let page = Math.floor(Math.random() * 5) + 1; // pages 1 à 5
    let listRes = http.get(`${BASE_URL}/app.php?page=${page}`);
    check(listRes, {
        'liste chargée': (r) => r.status === 200,
    });

    // -----------------------------
    // 2) RÉCUPÉRER UN ID DE LIVRE
    // -----------------------------
    let regex = /get\.php\?id=([a-f0-9]{24})/g;
    let ids = [...listRes.body.matchAll(regex)].map(m => m[1]);

    let id = ids.length > 0 ? ids[0] : null;

    // -----------------------------
    // 3) CONSULTER LE DÉTAIL D’UN LIVRE
    // -----------------------------
    if (id) {
        let detailRes = http.get(`${BASE_URL}/get.php?id=${id}`);
        check(detailRes, {
            'détails ok': (r) => r.status === 200,
        });
    }

    // -----------------------------
    // 4) AJOUTER UN LIVRE
    // -----------------------------
    let payload = {
        titre: `Livre Test ${Math.random()}`,
        auteur: "Testeur K6",
        siecle: "21",
    };

    let headers = { "Content-Type": "application/x-www-form-urlencoded" };

    let createRes = http.post(`${BASE_URL}/create.php`, payload, { headers });
    check(createRes, {
        'livre ajouté': (r) => r.status === 200 || r.status === 302,
    });

    // -----------------------------
    // 5) SUPPRESSION D’UN LIVRE
    // -----------------------------
    // pour cela, il faut récupérer l'id du livre ajouté → regex dans la réponse HTML
    let newIdMatch = /id=([a-f0-9]{24})/.exec(createRes.body);
    if (newIdMatch) {
        let deleteId = newIdMatch[1];
        let delRes = http.get(`${BASE_URL}/delete.php?id=${deleteId}`);
        check(delRes, {
            'livre supprimé': (r) => r.status === 200 || r.status === 302,
        });
    }

    sleep(1); // petite pause pour simuler un humain
}
