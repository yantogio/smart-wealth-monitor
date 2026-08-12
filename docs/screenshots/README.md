# Screenshots

The main README references three images from this folder. Until they exist, those
images render broken on GitHub — capture them before pushing.

| Filename | Page | What must be visible |
|---|---|---|
| `dashboard.png` | <http://localhost:8000/> | The amber "Potensi Diskon" card at the top **and** the price table with all four assets showing values |
| `watchlist.png` | <http://localhost:8000/watchlist> | The mini price chart rendered, plus the SMA column populated |
| `dca.png` | <http://localhost:8000/dca> | A completed simulation — filled form and the result figures below it |

## Capturing

```bash
php artisan migrate:fresh --seed   # guarantees fresh, populated demo data
npm run build                      # charts and styling need built assets
php artisan serve
```

Then capture each page at a desktop width (around 1440px). Crop out browser
chrome, bookmarks, and anything personal. PNG format.

The seeded demo currently flags **TLKM.JK** as a discount, so it will appear on
the dashboard card without any setup.
