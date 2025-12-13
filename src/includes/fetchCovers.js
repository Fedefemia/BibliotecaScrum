async function fetchCover(isbn) {
    try {
        const res = await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:${isbn}`);
        const data = await res.json();

        console.log("Raw API response for ISBN " + isbn, data);

        if (data.items && data.items.length) {
            for (const item of data.items) {
                const links = item.volumeInfo?.imageLinks;
                if (links) {
                    // Ordine di preferenza: thumbnail > smallThumbnail > small > medium > large > extraLarge
                    const cover = links.thumbnail || links.smallThumbnail || links.small || links.medium || links.large || links.extraLarge;
                    if (cover) return cover.replace(/^http:/, 'https:');
                }
            }
        }

        return 'src/assets/placeholder.jpg'; // fallback
    } catch(e) {
        console.error('Errore fetch copertina', isbn, e);
        return 'src/assets/placeholder.jpg';
    }
}