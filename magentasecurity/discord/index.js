// index.js (Discord-Bot)
require('dotenv').config();
const { Client, GatewayIntentBits, EmbedBuilder } = require('discord.js');
const fetch = require('node-fetch');

// Token und API-URL aus .env
const BOT_API_TOKEN = process.env.BOT_API_TOKEN;
const API_URL = process.env.API_URL;
const DISCORD_TOKEN = process.env.DISCORD_TOKEN;

const client = new Client({
    intents: [GatewayIntentBits.Guilds, GatewayIntentBits.GuildMessages, GatewayIntentBits.MessageContent]
});

client.once('ready', () => {
    console.log(`Bot ist online als ${client.user.tag}`);
});

client.on('messageCreate', async message => {
    if (message.author.bot) return;

    // Befehl zum Abrufen der Security-Infos
    if (message.content === '!securityinfo') {
        try {
            // API abrufen
            const response = await fetch(`${API_URL}/get`, {
                headers: { "Authorization": `Bearer ${BOT_API_TOKEN}` }
            });
            
            if (!response.ok) {
                return message.channel.send(`Fehler beim Abrufen der API: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Normale Nachricht mit allen Feldern
            let msgText = `**Security Info - ID ${data.id}**\n`;
            for (const [key, value] of Object.entries(data)) {
                if (typeof value !== 'object') {
                    msgText += `**${key}**: ${value}\n`;
                }
            }
            await message.channel.send(msgText);
            
            // Prüfen, ob ein Embed-Feld vorhanden ist
            for (const [key, value] of Object.entries(data)) {
                if (typeof value === 'string' && value.startsWith('embed=')) {
                    const embedUrl = value.split('embed=')[1];
                    
                    try {
                        const embedResp = await fetch(embedUrl);
                        const embedJson = await embedResp.json();
                        
                        // JSON als Embed parsen
                        const embed = new EmbedBuilder()
                            .setTitle(embedJson.title || `Details - ID ${data.id}`)
                            .setDescription(embedJson.description || '')
                            .setColor(embedJson.color ? parseInt(embedJson.color.replace('#', ''), 16) : 0x0099FF)
                            .setTimestamp();
                            
                        if (embedJson.fields && Array.isArray(embedJson.fields)) {
                            embed.addFields(embedJson.fields.map(f => ({
                                name: f.name || '\u200B',
                                value: f.value || '\u200B',
                                inline: f.inline || false
                            })));
                        }
                        
                        // DM an den User
                        await message.author.send({ embeds: [embed] });
                    } catch (err) {
                        console.error('Fehler beim Abrufen/Verarbeiten des Embeds:', err);
                        await message.channel.send('Konnte die Embed-Details nicht per DM senden.');
                    }
                }
            }
            
        } catch (err) {
            console.error('Fehler im Bot:', err);
            message.channel.send('Ein unerwarteter Fehler ist aufgetreten.');
        }
    }
});

// Bot-Login
client.login(DISCORD_TOKEN);
