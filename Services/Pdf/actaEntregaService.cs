using QuestPDF.Fluent;
using QuestPDF.Helpers;
using QuestPDF.Infrastructure;
using HELPDESK.Models.Inventario.EquipoUsuario;

namespace HELPDESK.Services.Pdf {
    public class actaEntregaService {
        public byte[] GenerarActa(equipoUsuarioModel asignacion, string entregadoPor) {
            QuestPDF.Settings.License = LicenseType.Community;

            byte[] pdfBytes = Document.Create(container => {
                container.Page(page => {
                    page.Size(PageSizes.Letter);
                    page.MarginHorizontal(50);
                    page.MarginVertical(40);

                    page.Header().Element(header => {
                        header.Row(row => {
                            row.RelativeItem(3).Column(col => {
                                col.Item().Text("ACTA DE ENTREGA DE EQUIPO")
                                    .FontSize(16).Bold().FontColor("#1a1a2e");
                            });

                            row.RelativeItem(1).AlignRight().Column(col => {
                                col.Item().Text("TRANSPORTE LOGISTICA")
                                    .FontSize(10).Bold().FontColor("#1a1a2e");
                                col.Item().Text("ALCOMEX")
                                    .FontSize(10).Bold().FontColor("#1a1a2e");
                            });
                        });
                    });

                    page.Content().Element(content => {
                        content.Column(col => {
                            col.Item().PaddingTop(10).Row(row => {
                                row.RelativeItem().Text($"Fecha: {asignacion.FechaAsignacion:dd/MM/yyyy}")
                                    .FontSize(11);
                            });

                            col.Item().PaddingTop(15).Text("Con la presente acta se le hace entrega de lo siguiente:")
                                .FontSize(11);

                            col.Item().PaddingTop(10).Table(table => {
                                table.ColumnsDefinition(columns => {
                                    columns.ConstantColumn(50);
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                });

                                table.Header(header => {
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Cantidad").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Descripcion").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Marca").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Modelo").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("IMEI").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Numero").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Condiciones").Bold().FontSize(9).FontColor("#ffffff");
                                });

                                table.Cell().Background("#f8f9fa").Padding(6).Text("1").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoNombre ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoMarca ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoModelo ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoNumeroSerie ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoNumeroSerie ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.Observaciones ?? "N/A").FontSize(10);
                            });

                            col.Item().PaddingTop(20).Column(legal => {
                                legal.Item().PaddingBottom(6).Text("El trabajador manifiesta que:").Bold().FontSize(11);
                                legal.Item().PaddingBottom(6).Text("1. El equipo asignado es propiedad de la empresa en todo momento y debe ser devuelto al terminar la relacion laboral o al recibir nuevo equipo.").FontSize(10);
                                legal.Item().PaddingBottom(6).Text("2. El trabajador se hace responsable del equipo asignado y se compromete a cuidarlo y mantenerlo en buen estado.").FontSize(10);
                                legal.Item().PaddingBottom(6).Text("3. En caso de perdida o dano, el trabajador autoriza el descuento de su salario para cubrir el costo del equipo.").FontSize(10);
                            });

                            col.Item().PaddingTop(40).Row(row => {
                                row.RelativeItem().Column(col => {
                                    col.Item().Text("Entregado por:").Bold().FontSize(11);
                                    col.Item().PaddingTop(50).LineHorizontal(1).LineColor("#1a1a2e");
                                    col.Item().PaddingTop(5).Text(entregadoPor).FontSize(10);
                                    col.Item().Text("Soporte Tecnico").FontSize(10).FontColor("#666666");
                                });

                                row.RelativeItem().Column(col => {
                                    col.Item().Text("Recibido por:").Bold().FontSize(11);
                                    col.Item().PaddingTop(50).LineHorizontal(1).LineColor("#1a1a2e");
                                    col.Item().PaddingTop(5).Text(asignacion.NombreUsuario).FontSize(10);
                                    col.Item().Text("Cuentas por cobrar").FontSize(10).FontColor("#666666");
                                });
                            });
                        });
                    });
                });
            }).GeneratePdf();

            return pdfBytes;
        }
    }
}
