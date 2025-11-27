defmodule App.Accounts.DataImporter do
  alias App.Accounts

  @male_names_url "https://api.dane.gov.pl/1.4/resources/63929/data?page=1&per_page=100&q=&sort=-col3"
  @female_names_url "https://api.dane.gov.pl/1.4/resources/63924/data?page=1&per_page=100&q=&sort=-col3"
  @male_surnames_url "https://api.dane.gov.pl/1.4/resources/63892/data?page=1&per_page=100&q=&sort=-col2"
  @female_surnames_url "https://api.dane.gov.pl/1.4/resources/63888/data?page=1&per_page=100&q=&sort=-col2"

  def run do
    tasks = [
      Task.async(fn -> fetch_data_from_api(@male_names_url) end),
      Task.async(fn -> fetch_data_from_api(@female_names_url) end),
      Task.async(fn -> fetch_data_from_api(@male_surnames_url) end),
      Task.async(fn -> fetch_data_from_api(@female_surnames_url) end)
    ]

    try do
      [male_names, female_names, male_surnames, female_surnames] =
        Task.await_many(tasks, 30_000)
      if Enum.empty?(male_names) do
        {:error, "Failed to retrieve data from male names API or data is empty."}
      else
        generate_and_save_users(male_names, female_names, male_surnames, female_surnames)
        {:ok, "100 users were imported from the PESEL register"}
      end
    rescue
      e ->
        Task.shutdown_many(tasks, :brutal_kill)
        {:error, "API communication error or timeout: #{Exception.message(e)}"}
    end
  end

  defp fetch_data_from_api(url) do
    case Req.get(url) do
      {:ok, %{status: 200, body: body}} ->
        body["data"]
        |> Enum.map(fn item ->
          item["attributes"]["col1"]["val"]
          |> to_string()
          |> format_name()
        end)

      _ ->
        IO.puts("Error downloading data from URL: #{url}")
        []
    end
  end

  defp generate_and_save_users(m_names, f_names, m_surnames, f_surnames) do
    1..100
    |> Enum.each(fn _ ->
      gender = Enum.random(["male", "female"])

      {first_name, last_name} =
        if gender == "male" do
          {Enum.random(m_names), Enum.random(m_surnames)}
        else
          {Enum.random(f_names), Enum.random(f_surnames)}
        end

      params = %{
        first_name: first_name,
        last_name: last_name,
        gender: gender,
        birthdate: random_date()
      }

      Accounts.create_user(params)
    end)
  end

  defp format_name(text) do
    text
    |> String.downcase()
    |> String.capitalize()
  end

  defp random_date do
    start_date = Date.new!(1970, 1, 1)
    end_date = Date.new!(2024, 12, 31)
    days_diff = Date.diff(end_date, start_date)

    Date.add(start_date, Enum.random(0..days_diff))
  end
end