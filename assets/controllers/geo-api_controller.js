import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['region', 'departement', 'city', 'postcode'];

    connect() {
        const regionCode = this.element.dataset.regionCode || null;
        const departementCode = this.element.dataset.departementCode || null;
        const cityName = this.element.dataset.cityName || null;

        // Charger les régions
        this.loadRegions(regionCode).then(() => {
            if (regionCode) {
                // Charger les départements si une région est sélectionnée
                this.loadDepartements(regionCode, departementCode).then(() => {
                    if (departementCode) {
                        // Charger les villes si un département est sélectionné
                        this.loadCities(departementCode, cityName);
                    }
                });
            }
        });
    }

    get_departement(event) {
        const code_region = event.target.value;
        this.loadDepartements(code_region);
    }

    get_city(event) {
        const code_departement = event.target.value;
        this.loadCities(code_departement);
    }

    async get_postcode(event) {
        const cityName = event.target.value;
        const departementCode = this.departementTarget.value;

        if (cityName && departementCode) {
            try {
                const response = await fetch(`https://geo.api.gouv.fr/departements/${departementCode}/communes`);
                const data = await response.json();
                const city = data.find(city => city.nom === cityName);

                if (city) {
                    this.postcodeTarget.value = city.codesPostaux[0]; // Remplit le premier code postal
                }
            } catch (error) {
                console.error('Erreur lors du chargement du code postal :', error);
            }
        }
    }
            async loadRegions(selectedRegionCode = null) {
                try {
                    const response = await fetch('https://geo.api.gouv.fr/regions');
                    const data = await response.json();
                    this.regionTarget.innerHTML = '<option value="">Sélectionnez une région</option>';
                    data.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.code;
                        option.textContent = region.nom;
                        if (region.code === selectedRegionCode) {
                            option.selected = true;
                        }
                        this.regionTarget.appendChild(option);
                    });
                } catch (error) {
                    console.error('Erreur lors du chargement des régions :', error);
                }
            }

            async loadDepartements(regionCode, selectedDepartementCode = null) {
                try {
                    const response = await fetch(`https://geo.api.gouv.fr/regions/${regionCode}/departements`);
                    const data = await response.json();
                    this.departementTarget.innerHTML = '<option value="">Sélectionnez un département</option>';
                    data.forEach(departement => {
                        const option = document.createElement('option');
                        option.value = departement.code;
                        option.textContent = departement.nom;
                        if (departement.code === selectedDepartementCode) {
                            option.selected = true;
                        }
                        this.departementTarget.appendChild(option);
                    });
                } catch (error) {
                    console.error('Erreur lors du chargement des départements :', error);
                }
            }

            async loadCities(departementCode, selectedCityName = null) {
                try {
                    const response = await fetch(`https://geo.api.gouv.fr/departements/${departementCode}/communes`);
                    const data = await response.json();
                    console.log(data);
                    this.cityTarget.innerHTML = '<option value="">Sélectionnez une ville</option>';
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.nom;
                        option.textContent = city.nom;

                        if (city.nom === selectedCityName) {
                            option.selected = true;
                        }
                        this.cityTarget.appendChild(option);
                    });
                } catch (error) {
                    console.error('Erreur lors du chargement des villes :', error);
                }
            }
        }